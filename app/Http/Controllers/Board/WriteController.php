<?php

namespace App\Http\Controllers\Board;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Write;
use App\Point;
use App\Board;
use App\BoardFile;
use App\BoardGood;
use App\Comment;
use App\Notification;
use App\ReCaptcha;
use Auth;
use Cache;
use App\Services\RssFeed;
use Illuminate\Pagination\Paginator;

class WriteController extends Controller
{
    public $writeModel;
    public $boardFileModel;
    public $boardGoodModel;

    public function __construct(Request $request, Write $write, BoardFile $boardFile, BoardGood $boardGood)
    {
        $this->writeModel = $write;
        $this->writeModel->board = Board::getBoard($request->boardId);
        $table = is_null($this->writeModel->board) ? '' : $this->writeModel->board->table_name;
        $this->writeModel->setTableName($table);
        $this->boardFileModel = $boardFile;
        $this->boardGoodModel = $boardGood;
    }
    /**
     * Display a listing of the resource.
     *
     * @param integer $boardId
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $boardId)
    {
        $params = $this->writeModel->getIndexParams($this->writeModel, $request);

        $skin = $this->writeModel->board->skin ? : 'default';

        return viewDefault("board.$skin.index", $params);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request, $boardId, $writeId)
    {
        $board = Board::getBoard($boardId);
        // 글 보기 데이터
        $params = $this->writeModel->getViewParams($this->writeModel, $writeId, $request);

        // 댓글 데이터
        $comment = new Comment();
        $params = array_collapse([$params, $comment->getCommentsParams($this->writeModel, $writeId, $request)]);

        // 전체 목록 보기 선택시 목록 데이터
        if($board->use_list_view) {
            $params = array_collapse([$params, $this->writeModel->getIndexParams($this->writeModel, $request)]);
        }
        // 이전글, 다음글 데이터 추가
        $params = array_collapse([$params, $this->writeModel->getPrevNextView($this->writeModel, $writeId, $request)]);

        // 요청 URI 추가
        $params = array_add($params, 'requestUri', $request->getRequestUri());

        // 현재 사용자 추가
        $params = array_add($params, 'user', auth()->user());

        $skin = $board->skin ? : 'default';

        return viewDefault("board.$skin.view", $params);
    }

    // 글 보기 중 링크 연결
    public function link($boardId, $writeId, $linkNo)
    {
        $linkUrl = $this->writeModel->beforeLink($this->writeModel, $writeId, $linkNo);

        return view('board.link', [ 'linkUrl' => $linkUrl ]);
    }

    // 추천/비추천 ajax 메서드
    public function good($boardId, $writeId, $good)
    {
        $count = $this->boardGoodModel->good($this->writeModel, $writeId, $good);

        return [ 'count' => $count ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $boardId)
    {
        $params = $this->writeModel->getCreateParams($request);
        $skin = $this->writeModel->board->skin ? : 'default';

        return viewDefault("board.$skin.form", $params);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $boardId)
    {
        if(auth()->guest() || (!auth()->user()->isSuperAdmin() && $this->writeModel->board->use_recaptcha)) {
            ReCaptcha::reCaptcha($request);
        }

        $writeId = $this->writeModel->storeWrite($this->writeModel, $request);

        if(count($request->attach_file) > 0) {
            try {
                $this->boardFileModel->createBoardFiles($request, $boardId, $writeId);
            } catch(Exception $e) {
            }
        }

        if(cache('config.email.default')->emailUse && $this->writeModel->board->use_email) {
            $notification = new Notification();
            $notification->sendWriteNotification($this->writeModel, $writeId);
        }

        return redirect(route('board.view', ['boardId' => $boardId, 'writeId' => $writeId] ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($boardId, $writeId, Request $request)
    {
        $params = $this->writeModel->getEditParams($writeId, $this->writeModel, $request);
        $skin = $this->writeModel->board->skin ? : 'default';

        return viewDefault("board.$skin.form", $params);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $boardId, $writeId)
    {
        $fileCount = 0;
        if(count($request->file_del) > 0 || count($request->attach_file) > 0) {
            // 첨부 파일 변경
            $fileCount = $this->boardFileModel->updateBoardFiles($request, $boardId, $writeId);
        }
        // 게시 글 수정
        $this->writeModel->updateWrite($this->writeModel, $request, $writeId, $fileCount);

        return redirect(route('board.view', ['boardId' => $boardId, 'writeId' => $writeId] ));
    }

    /**
     * Show the form for editing the specified resource.
     * 글 답변 폼 연결
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createReply($boardId, $writeId, Request $request)
    {
        $params = $this->writeModel->getReplyParams($writeId, $this->writeModel, $request);
        $skin = $this->writeModel->board->skin ? : 'default';

        return viewDefault("board.$skin.form", $params);
    }

    /**
     * 글보기 - 삭제
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $boardId, $writeId)
    {
        $message = $redirect = '';

        try {
            $this->writeModel->deleteWriteCascade($this->writeModel, $writeId);
        } catch (Exception $e) {
            $redirect = route('board.index', $boardId);
            return alertRedirect($e->getMessage(), $redirect);
        }

        $returnUrl = route('board.index', $boardId). ($request->page == 1 ? '' : '?page='. $request->page);
        return redirect($returnUrl);
    }

    /**
     * 게시판 글 목록 - 선택 삭제
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function selectedDelete(Request $request, $boardId, $writeId)
    {
        $ids = explode(',', $writeId);
        foreach($ids as $id) {
            try {
                $this->writeModel->deleteWriteCascade($this->writeModel, $id);
            } catch (Exception $e) {
                $redirect = route('board.index', $boardId);
                return alertRedirect("($id번 글) ". $e->getMessage(), $redirect);
            }
        }

        $returnUrl = route('board.index', $boardId). ($request->page == 1 ? '' : '?page='. $request->page);
        return redirect($returnUrl);
    }

    // RSS 보기
    public function rss(Request $request, $boardId, RssFeed $feed)
    {
        $rss = $feed->getRSS($boardId);

        return response($rss)
            ->header('Content-type', 'text/xml')
            ->header('Cache-Control', 'no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('charset', 'utf-8');
    }

}

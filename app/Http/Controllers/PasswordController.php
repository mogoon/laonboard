<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Board;
use App\Write;
use App\User;
use Auth;
use Hash;

class PasswordController extends Controller
{
    public $writeModel;

    public function __construct(Request $request, Write $write)
    {
        $this->writeModel = $write;
        $this->writeModel->board = Board::getBoard($request->boardName, 'table_name');
        $this->writeModel->setTableName($request->boardName);
    }

    // 비밀번호 입력 폼 연결
    public function checkPassword(Request $request, $type)
    {
        $boardName = $request->boardName ? : '';
        $writeId = $request->writeId ? : 0;
        $commentId = $request->commentId ? : 0;

        $subject = '';
        if($type == 'commentDelete') {         	// 댓글 삭제
            $subject = '댓글 삭제';
        } else if($type == 'writeDelete'){     	// 글 삭제
            $subject = '글 삭제';
        } else if($type == 'writeEdit'){    	// 글 수정
            $subject = '글 수정';
        } else if($type == 'secret'){       	// 비밀 글
            $subject = Write::getWrite($this->writeModel->board->id, $writeId)->subject;
        }

        $skin = $this->writeModel->board->skin ? : 'default';
        $params = [
            'subject' => $subject,
            'boardName' => $boardName  ,
            'board' => $this->writeModel->board,
            'writeId' => $writeId,
            'commentId' => $commentId,
            'type' => $type,
            'nextUrl' => $request->nextUrl
        ];

        return viewDefault("board.$skin.password", $params);
    }

    // 비밀번호 비교
    public function comparePassword(Request $request)
    {
        $boardName = $request->boardName ? : '';
        $writeId = $request->writeId;
        if($request->commentId) {
            $writeId = $request->commentId;
        }
        $write = Write::getWrite($this->writeModel->board->id, $writeId);

        // 입력한 비밀번호와 작성자의 글 비밀번호를 비교한다.
        if( Hash::check($request->password, $write->password) ) {
            if(strpos(strtolower($request->type), 'delete')) {
                session()->put(session()->getId(). 'delete_board_'. $boardName. '_write_'. $writeId, true);
            } else if(strpos(strtolower($request->type), 'edit')) {
                session()->put(session()->getId(). 'edit_board_'. $boardName. '_write_'. $writeId, true);
            } else {
                session()->put(session()->getId(). 'secret_board_'. $boardName. '_write_'. $writeId, true);
            }

            return redirect($request->nextUrl);
        } else {
            return view('message', [
                'message' => '비밀번호가 틀립니다.',
            ]);
        }
    }
}
<?php

namespace App;

use App\Write;
use App\User;
use App\Group;
use Cache;
use Mail;
use App\Mail\WriteNotification;
use App\Mail\CongratulateJoin;
use App\Mail\JoinNotification;
use App\Mail\EmailCertify;

class Notification
{
    public function sendWriteNotification($writeModel, $writeId)
    {
        $board = $writeModel->board;
        $boardAdmin = $board->admin;    // 게시판 관리자
        $groupAdmin = Cache::remember("group.{$board->group_id}.admin", config('gnu.CACHE_EXPIRE_MINUTE'), function() use($board) {
            return Group::find($board->group_id)->admin;
        });     // 그룹 관리자
        $superAdmin = Cache::get('config.homepage')->superAdmin;    // 최고 관리자
        $write = $writeModel->find($writeId);   // 작성한 글
        $parentWrite = $writeModel->find($write->parent);   // 원글
        $content = $write->content;     // 글 내용
        $writeSubject = $write->subject;    // 글 제목
        $name = $write->name;
        $type = '새';
        $tag = '';
        if($write->is_comment) {
            $type = '코멘트';
            $tag = '#comment'. $write->id;
        } else if($write->reply) {
            $type = '답변';
        };
        // 메일 제목
        $mailSubject = '['. Cache::get('config.homepage')->title. '] '. $board->subject. ' 게시판에 '. $type. '글이 올라왔습니다.';
        // 게시글 링크 주소
        $linkUrl = route('board.view', ['boardId' => $board->id, 'writeId' => $parentWrite->id]). $tag;

        $arrayEmail = [];
        $mailConfig = Cache::get('config.email.board');
        // 최고관리자에게 보내는 메일
        if($mailConfig->emailWriteSuperAdmin) {
            $arrayEmail[] = $superAdmin;
        }
        // 게시판그룹관리자에게 보내는 메일
        if($mailConfig->emailWriteGroupAdmin) {
            $arrayEmail[] = $groupAdmin;
        }
        // 게시판관리자에게 보내는 메일
        if($mailConfig->emailWriteBoardAdmin) {
            $arrayEmail[] = $boardAdmin;
        }
        // 원글게시자에게 보내는 메일
        if($mailConfig->emailWriter) {
            $arrayEmail[] = $parentWrite->email;
        }
         // 옵션에 메일받기가 체크되어 있다면
        if(strstr($parentWrite['option'], 'mail')) {
            $arrayEmail[] = $parentWrite->email;
        }
        // 중복된 메일 주소는 제거
        $uniqueEmail = array_unique($arrayEmail);
        $uniqueEmail = array_values($uniqueEmail);

        foreach($uniqueEmail as $to) {
            Mail::to($to)->send(new WriteNotification($mailSubject, $writeSubject, $name, $content, $linkUrl));
        }
    }

    // 가입한 회원에게 가입 축하 메일 보내기
    public function sendCongratulateJoin($user)
    {
        $subject = '['. Cache::get('config.homepage')->title. '] 회원가입을 축하드립니다.';
        Mail::to($user)->send(new CongratulateJoin($user, $subject));
    }

    // 최고관리자에게 회원 가입 알림 메일 보내기
    public function sendJoinNotification($user)
    {
        $subject = '['. Cache::get('config.homepage')->title. '] '. $user->nick. ' 님께서 회원으로 가입하셨습니다.';
        Mail::to(Cache::get('config.homepage')->superAdmin)->send(new JoinNotification($user, $subject));
    }

    // 회원 정보 수정에서 이메일 변경시 이메일 인증 메일 발송
    public function sendEmailCertify($to, $user, $nick, $isEmailChange)
    {
        Mail::to($to)->send(new EmailCertify($user, $nick, $isEmailChange));
    }
}

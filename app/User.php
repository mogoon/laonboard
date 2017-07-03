<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\SocialLogin;
use App\Point;
use App\Group;
use App\Write;
use Auth;
use DB;
use Cache;
use App\Common\Util;
use App\Notification;
use App\GroupUser;
use Carbon\Carbon;


class User extends Authenticatable
{
    use Notifiable;

    protected $dates = ['today_login', 'email_certify', 'nick_date', 'open_date', ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public $rulesRegister = [
        'email' => 'required|email|max:255|unique:users',
        'password_confirmation' => 'required',
        'nick' => 'required|nick_length:2,4|unique:users|alpha_num',
    ];

    public $rulesPassword = [
        'password_confirmation' => 'required',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    // SocialLogin 모델과의 관계 설정
    public function socialLogins()
    {
        return $this->hasMany(SocialLogin::class);
    }

    // BoardGroup 모델과의 관계 설정
    public function groups()
    {
        return $this->belongsToMany(Group::class)->withPivot('id', 'created_at');
    }

    // Point 모델과의 관계설정
    public function points()
    {
        return $this->hasMany(Point::class);
    }

    // Write 모델과의 관계설정
    public function writes()
    {
        return $this->hasMany(Write::class);
    }

    public function isAdmin()
    {
        if($this->isSuperAdmin()) {
            return true;
        }

        if(ManageAuth::where('user_id', auth()->user()->id)->first()) {
            return true;
        }

        return false;
    }

    public function isSuperAdmin()
    {
        if(auth()->user()->email === Cache::get('config.homepage')->superAdmin) {
            return true;
        }
        return false;
    }

    public function isGroupAdmin($group)
    {
        if(auth()->user()->email === $group->admin) {
            return true;
        }
        return false;
    }

    public function isBoardAdmin($board)
    {
        if(auth()->user()->email === $board->admin) {
            return true;
        }
        return false;
    }

    // 추천인 닉네임 구하기
    public function recommendedPerson($user)
    {
        $recommendedNick = '';
        if(!is_null($user->recommend)) {
            $recommendedNick = User::where([
                'id' => $user->recommend,
            ])->first()->nick;
        }

        return $recommendedNick;
    }

    // 회원 정보 수정 페이지에 전달할 데이터
    public function editFormData($config)
    {
        $user = Auth::user();

        // 정보공개 변경여부
        $openChangable = $this->openChangable($user, Carbon::now());

        $SocialLogin = SocialLogin::where('user_id', $user->id)->get();
        $socials = [
            'naver' => '',
            'google' => '',
            'facebook' => '',
        ];

        foreach($SocialLogin as $sociallogin) {
            if($sociallogin['provider'] == 'naver') {
                $socials['naver'] = $sociallogin['social_id'];
            }
            if($sociallogin['provider'] == 'google') {
                $socials['google'] = $sociallogin['social_id'];
            }
            if($sociallogin['provider'] == 'facebook') {
                $socials['facebook'] = $sociallogin['social_id'];
            }
        }

        $editFormData = [
            'user' => $user,
            'config' => $config,
            'openDate' => Cache::get("config.homepage")->openDate,                      // 정보공개 변경 가능 일
            'nickChangable' => $this->nickChangable($user, Carbon::now(), $config),     // 닉네임 변경여부
            'openChangable' => $openChangable[0],                                       // 정보공개 변경 여부
            'dueDate' => $openChangable[1],                                             // 정보공개 언제까지 변경 못하는지 날짜
            'recommend' => $this->recommendedPerson($user),                             // 추천인 닉네임 id로 가져오기
            'socials' => $socials,                                                      // 소셜에 연결한 정보
        ];

        return $editFormData;
    }

    // 닉네임 변경 가능 여부
    public function nickChangable($user, $current, $config)
    {
        // 현재 시간과 로그인한 유저의 닉네임변경시간과의 차이
        $nickDiff = $current->diffInDays($user->nick_date);
        // 닉네임 변경 여부
        $nickChangable = false;
        if($nickDiff > $config->nickDate) {
            $nickChangable = true;
        }

        return $nickChangable;
    }

    // 정보공개 변경 가능 여부
    public function openChangable($user, $current)
    {
        $config = Cache::get("config.homepage");
        $openChangable = array(false, $current);

        $openDate = $user->open_date;

        if(is_null($openDate)) {
            $openChangable[0] = true;
        } else {
            $openDiff = $current->diffInDays($openDate);
            if($openDiff >= $config->openDate) {
                $openChangable[0] = true;
            }
            $openChangable[1] = $openDate->addDays($config->openDate);
        }

        return $openChangable;
    }

    // 비밀번호 설정
    public function setPassword($request)
    {
        $user = Auth::user();
        $user->password = bcrypt($request->get('password'));
        $user->save();
    }

    // 회원 가입
    public function joinUser($request, $config)
    {
        $nowDate = Carbon::now()->toDateString();

        $userInfo = [
            'email' => $request->get('email'),
            'password' => $request->has('password') ? bcrypt($request->get('password')) : '',
            'nick' => $request->get('nick'),
            'nick_date' => $nowDate,
            'mailing' => 0,
            'sms' => 0,
            'open' => 1,
            'open_date' => $nowDate,
            'today_login' => Carbon::now(),
            'login_ip' => $request->ip(),
            'ip' => $request->ip(),
        ];

        // 이메일 인증을 사용할 경우
        if(Cache::get('config.email.default')->emailCertify) {
            $addUserInfo = [
                'email_certify' => null,
                // 라우트 경로 구분을 위해 /는 제거해 줌.
                'email_certify2' => str_replace("/", "-", bcrypt($request->ip() . Carbon::now()) ),
                'level' => 1,   // 인증하기 전 회원 레벨은 1
            ];
            $userInfo = array_collapse([$userInfo, $addUserInfo]);
        } else {    // 이메일 인증을 사용하지 않을 경우
            $addUserInfo = [
                'email_certify' => Carbon::now(),
                'level' => $config->joinLevel,
            ];

            $userInfo = array_collapse([$userInfo, $addUserInfo]);
        }
        // 회원정보로 유저를 추가한다.
        $user = User::create($userInfo);

        // 회원 가입 축하 포인트 부여
        $point = new Point();
        $point->insertPoint($user->id, Cache::get("config.join")->joinPoint, '회원가입 축하', '@users', $user->email);

        // Users 테이블의 주 키인 id의 해시 값을 만들어서 저장한다. (게시글에 사용자 번호 노출 방지)
        $user->id_hashkey = str_replace("/", "-", bcrypt($user->id));

        $user->save();

        $notification = new Notification();
        // 회원 가입 축하 메일 발송 (인증도 포함되어 있음)
        if(Cache::get('config.email.join')->emailJoinUser) {
            $notification->sendCongratulateJoin($user);
        }
        // 최고관리자에게 회원 가입 알림 메일 발송
        if(Cache::get('config.email.join')->emailJoinSuperAdmin) {
            $notification->sendJoinNotification($user);
        }

        return $user;
    }

    // 회원 정보 수정
    public function updateUserInfo($request, $config)
    {
        $user = Auth::user();
        $openChangable = $this->openChangable($user, Carbon::now());

        // 현재 시간 date type으로 받기
        $nowDate = Carbon::now()->toDateString();

        // 추천인 닉네임 받은 것을 해당 닉네임의 id로 조회
        $recommendedId = '';
        if($request->has('recommend')) {
            $recommendedUser = User::where([
                'nick' => $request->get('recommend'),
            ])->first();

            if(is_null($recommendedUser)) {
                return 'notExistRecommend';
            }
            $recommendedId = $recommendedUser->id;

            // 추천인에게 포인트 부여
            $point = new Point();
            $point->insertPoint($recommendedUser->id, Cache::get("config.join")->recommendPoint, $user->email . '의 추천인', '@users', $recommendedUser->email, $user->email . ' 추천');

            $recommendedUser->save();
        }

        $toUpdateUserInfo = [
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'id_hashkey' => str_replace("/", "-", bcrypt($user->id)),  // 회원정보수정때마다 id_hashkey를 변경한다.
            'name' => $request->get('name'),
            'nick' => $request->has('nick') ? $request->get('nick') : $user->nick,
            'nick_date' => $request->has('nick') ? $nowDate : $user->nick_date,
            'homepage' => $request->get('homepage'),
            'hp' => $request->get('hp'),
            'tel' => $request->get('tel'),
            'addr1' => $request->get('addr1'),
            'addr2' => $request->get('addr2'),
            'zip' => $request->get('zip'),
            'signature' => $request->get('signature'),
            'profile' => $request->get('profile'),
            'memo' => $request->get('memo'),
            'mailing' => $request->has('mailing') ? $request->get('mailing') : 0,
            'sms' => $request->has('sms') ? $request->get('sms') : 0,
            'recommend' => $request->has('recommend') ? $recommendedId : $user->recommend,
        ];

        // 정보공개 체크박스에 체크를 했거나 기존에 open값과 open입력값이 다르다면 기존 open 값에 open 입력값을 넣는다.
        if($request->has('open') || $user->open != $request->get('open')) {
            $toUpdateUserInfo = array_collapse([ $toUpdateUserInfo, [
                'open' => $request->get('open'),
                'open_date' => $nowDate
            ] ]);
        }

        $isEmailChange = $request->get('email') != $user->email;
        // 이메일 인증을 사용하고 이메일이 변경될 경우 이메일 인증을 다시 해야한다.
        if(Cache::get('config.email.default')->emailCertify && $isEmailChange) {
            $toUpdateUserInfo = array_collapse([ $toUpdateUserInfo, [
                'email_certify' => null,
                // 라우트 경로 구분을 위해 /는 제거해 줌.
                'email_certify2' => str_replace("/", "-", bcrypt($request->ip() . Carbon::now()) ),
                'level' => 1,   // 인증하기 전 회원 레벨은 1
            ] ]);

            $user->update($toUpdateUserInfo);
            // 이메일 인증 메일 발송
            $notification = new Notification();
            $notification->sendEmailCertify($request->get('email'), $user, $toUpdateUserInfo['nick'], $isEmailChange);
        } else {
            $user->update($toUpdateUserInfo);
        }

        return 'finishUpdate';
    }

    // 회원 정보 수정에서 소셜 연결 해제
    public function disconnectSocialAccount($request)
    {
        return SocialLogin::where([
            'provider' => $request->get('provider'),
            'social_id' => $request->get('social_id'),
            'user_id' => $request->get('user_id'),
        ])->delete();
    }

    // 회원 정보 수정에서 소셜 계정 연결
    public function connectSocialAccount($userFromSocial, $provider, $request)
    {
        $user = Auth::user();
        // 로그인한 유저가 연결된 소셜 로그인 정보가 있는지 확인
        $socialLogin = SocialLogin::where([
            'provider' => $provider,
            'social_id' => $userFromSocial->getId(),
            'user_id' => $user->id,
        ])->first();

        if(is_null($socialLogin)) {
            // 소셜로그인 정보 등록
            $socialLogin = new SocialLogin([
                'provider' => $provider,
                'social_id' => $userFromSocial->getId(),
                'social_token' => $userFromSocial->token,
                'ip' => $request->ip(),
            ]);

            // User 모델과 SocialLogin 모델의 관계를 이용해서 social_logins 테이블에 가입한 user_id와 소셜 데이터 저장.
            $user->socialLogins()->save($socialLogin);

            return '소셜 계정이 연결되었습니다.';
        } else {
            // 이미 연결된 계정이라는 안내 메세지 보내 줌
            return '이미 연결된 계정입니다.';
        }
    }

    // 메일 인증 메일주소 변경
    public function changeCertifyEmail($request)
    {
        $beforeEmail = $request->beforeEmail;
        $user = User::where('email', $beforeEmail)->first();
        $user->email = $request->email;
        $user->save();

        // 이메일 인증 메일 발송
        $notification = new Notification();
        $notification->sendEmailCertify($user->email, $user, $user->nick, true);

        return $user->email;
    }

    // 자기소개에 필요한 파라미터 가져오기
    public function getProfileParams($id)
    {
        if(mb_strlen($id, 'utf-8') > 10) {  // 커뮤니티 쪽에서 들어올 때 user의 id가 아닌 id_hashKey가 넘어온다.
            $user = User::where('id_hashkey', $id)->first();
        } else {
            $user = User::find($id);
        }
        if(is_null($user)) {
            return '회원정보가 존재하지 않습니다.\\n\\n탈퇴하였을 수 있습니다.';
        }
        $loginedUser = auth()->user();
        if(!$loginedUser->open && !$loginedUser->isSuperAdmin() && $loginedUser->id != $user->id) {
            return '자신의 정보를 공개하지 않으면 다른분의 정보를 조회할 수 없습니다.\\n\\n정보공개 설정은 회원정보수정에서 하실 수 있습니다.';
        }

        if(!$user->open && !$loginedUser->isSuperAdmin() && $loginedUser->id != $user->id) {
            return '정보공개를 하지 않았습니다.';
        }

        // 가입일과 오늘 날짜와의 차이
        $current = Carbon::now();
        $joinDay = $user->created_at;
        $diffDay = $current->diffInDays($joinDay);

        return [
            'user' => $user,
            'diffDay' => $diffDay
        ];
    }

    // 회원 탈퇴
    public function leaveUser()
    {
        $user = auth()->user();
        if($user->isSuperAdmin()) {
            return '최고 관리자는 탈퇴할 수 없습니다';
        }
        $user->update([
            'leave_date' => Carbon::now()->format('Ymd')
        ]);

        Auth::logout();

        return $user->nick. '님께서는 '. Carbon::now()->format('Y년 m월 d일'). '에 회원에서 탈퇴하셨습니다.';
    }

}

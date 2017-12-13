@extends('admin.layouts.basic')

@section('title')캐시 일괄삭제 | {{ cache("config.homepage")->title }}@endsection

@section('content')
<div class="body-head">
    <div class="pull-left">
        <h3>최신글 캐시파일 일괄삭제</h3>
        <ul class="fl">
            <li class="admin">Admin</li>
            <li class="depth">환경 설정</li>
            <li class="depth">캐시파일 일괄삭제</li>
        </ul>
    </div>
</div>
@if(notNullCount($caches) == 0)
    <div id="body_tab_type2">
        <span class="txt">삭제할 최신글 캐시파일이 없습니다.</span>
    </div>
@else
<div id="body_tab_type2">
    <span class="txt">완료 메세지가 나오기 전에 프로그램의 실행을 중지하지 마십시오.</span>
</div>
<div class="body-contents">
    <ul class="file_delete">
        <?php
            $count = 0;
            foreach($caches as $cache) {
                $count++;
                echo "<li>$cache</li>";
                cache()->forget($cache);
            }
        ?>
        <li>완료됨</li>
    </ul>

    <div class="file_delete_txt">
        <p><span class="success">최신글 캐시파일 {{ $count }}건 삭제 완료됐습니다.</span><br>
        프로그램의 실행을 끝마치셔도 좋습니다.<p>
    </div>
</div>
@endif

<script>
    var menuVal = 100710;
</script>
@endsection

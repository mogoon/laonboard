@extends('install.layout')

@section('title')
    {{  config('app.name')." 라이센스 확인 1/3" }}
@endsection

@section('step')
    INSTALLATION
@endsection

@section('content')

<form action="{{ route('install.form') }}" method="post" onsubmit="return frm_submit(this);">

<div class="ins_inner">
    <p>
        <strong class="st_strong">라이센스(License) 내용을 반드시 확인하십시오.</strong><br>
        라이센스에 동의하시는 경우에만 설치가 진행됩니다.
    </p>

    <div class="ins_ta ins_license">
        <textarea name="license" id="license" readonly></textarea>
    </div>

    <div id="ins_agree">
        <label for="agree">동의합니다.</label>
        <input type="checkbox" name="agree" value="동의함" id="agree">
    </div>

    <div class="inner_btn">
        <input type="submit" value="다음">
    </div>
</div>

</form>

<script>
function frm_submit(f)
{
    if (!f.agree.checked) {
        alert("라이센스 내용에 동의하셔야 설치가 가능합니다.");
        return false;
    }
    return true;
}
</script>
@endsection

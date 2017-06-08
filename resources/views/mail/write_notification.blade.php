<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>{{ $subject }} 메일</title>
</head>

<body>

<div style="margin:30px auto;width:600px;border:10px solid #f7f7f7">
    <div style="border:1px solid #dedede">
        <h1 style="padding:30px 30px 0;background:#f7f7f7;color:#555;font-size:1.4em">
            {{ $subject }}
        </h1>
        <span style="display:block;padding:10px 30px 30px;background:#f7f7f7;text-align:right">
            작성자 {{ $name }}
        </span>
        <div style="margin:20px 0 0;padding:30px 30px 50px;min-height:200px;height:auto !important;height:200px;border-bottom:1px solid #eee">
            {{ $content }}
        </div>
        <a href="{{ $linkUrl }}" style="display:block;padding:30px 0;background:#484848;color:#fff;text-decoration:none;text-align:center">사이트에서 게시물 확인하기</a>
    </div>
</div>

</body>
</html>

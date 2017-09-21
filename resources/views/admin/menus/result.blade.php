@if($type)
<table class="table box">
    <thead>
        <tr>
            <th scope="col">제목</th>
            <th scope="col" class="td_mngsmall">선택</th>
        </tr>
    </thead>
    <tbody>
        @if(count($results) > 0)
        @foreach($results as $result)
        <tr>
            <td>{{ $result['subject'] }}</td>
            <td class="td_mngsmall text-center">
                <input type="hidden" name="subject[]" value="{{ preg_replace('/[\'\"]/', '', $result['subject']) }}">
                <input type="hidden" name="link[]" value=
                    @if($type == 'group') {{ route('group', $result['group_id']) }}
                    @elseif($type == 'board') {{ route('board.index', $result['table_name']) }}
                    @elseif($type == 'content') {{ route('content.show', $result['content_id']) }}
                    @endif
                >
                <button type="button" class="btn btn-default add_select">선택</button>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
@else
<div class="form-group">
    <label for="name" class="col-sm-2 col-xs-3 control-label" style="text-align: left;">메뉴</label>
    <div class="col-sm-3 col-xs-9">
        <input type="text" class="form-control required" name="name" id="name" required>
    </div>
</div>

<div class="form-group">
    <label for="link" class="col-sm-2 col-xs-3 control-label" style="text-align: left;">링크</label>
    <div class="col-sm-5 col-xs-9">
        <input type="text" class="form-control required" name="link" id="link" value="http://" required>
        <span class="help-block">링크는 http://를 포함해서 입력해 주세요.</span>
    </div>
</div>
@endif

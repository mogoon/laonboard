@extends('admin.admin')

@section('title')
    회원 관리 | {{ $title }}
@endsection

@section('include_script')
    <script src="{{ asset('js/common.js') }}"></script>
@endsection

@section('content')
<div class="body-head">
    <div class="pull-left">
        <h3>회원관리</h3>
        <ul class="fl">
            <li class="admin">Admin</li>
            <li class="depth">회원관리</li>
            <li class="depth">회원목록</li>
        </ul>
    </div>
    <div class="pull-right">
        <ul class="mb_btn" style="margin-top:8px;">
            <li>
                <a class="btn btn-default" href="{{ route('admin.users.create')}}" role="button">회원추가</a>
            </li>
            <li>
                <input type="button" id="selected_update" class="btn btn-default" value="선택수정">
            </li>
            <li>
                <input type="button" id="selected_delete" class="btn btn-default" value="선택삭제">
            </li>
        </ul>
    </div>
</div>

<div class="body-contents">
    @if(Session::has('message'))
        <div class="alert alert-info">
            {{ Session::get('message') }}
        </div>
    @endif

    <div id="mb" class="">
        <ul class="mb_btn mb10 pull-left">
            <li>
                <button type="" class="btn btn-sir pull-left">전체보기</button>
            </li>
            <li>
                <span class="total">총회원수 0명 중, 차단 0명, 탈퇴 0명</span>
            </li>
        </ul>
        <div class="mb_sch mb10 pull-right">
            <form>
                <label for="" class="sr-only">검색대상</label>
                <select name="" id="">
                    <option value="">회원이메일</option>
                    <option value="">닉네임</option>
                    <option value="">권한</option>
                    <option value="">가입일</option>
                    <option value="">최종접속일</option>
                    <option value="">IP</option>
                    <option value="">추천인</option>
                </select>
                <label for="" class="sr-only">검색어</label>
                <input type="text" name="" value="" id="" class="search" required>
                <button type="submit" id="" class="search-icon">
                    <i class="fa fa-search" aria-hidden="true"></i><span class="sr-only">검색</span>
                </button>
            </form>
        </div>

        <form class="form-horizontal" role="form" method="POST" id="selectForm" action="">
            <input type="hidden" id='ids' name='ids' value='' />
            <input type="hidden" id='opens' name='opens' value='' />
            <input type="hidden" id='mailings' name='mailings' value='' />
            <input type="hidden" id='smss' name='smss' value='' />
            <input type="hidden" id='intercepts' name='intercepts' value='' />
            <input type="hidden" id='levels' name='levels' value='' />
            <input type="hidden" id='_method' name='_method' value='' />
            {{ csrf_field() }}
            <table class="table table-striped box">
                <thead>
                    <th class="td_chk"><input type="checkbox" name="chkAll" onclick="checkAll(this.form)"/></th>
                    <th>회원이메일</th>
                    <th>닉네임</th>
                    <th>상태/권한</th>
                    <th>포인트</th>
                    <th>가입일</th>
                    <th>최종접속</th>
                    <th>접근그룹</th>
                    <th>관리</th>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><input type="checkbox" name="chkId[]" class="userId" value='{{ $user->id }}' /></td>
                        <td class="text-left">
                            <div class="mb_tooltip">
                                {{ $user->email }}
                                <span class="tooltiptext">ip주소</span>
                            </div>
                        </td>
                        <td class="text-left">{{ $user->nick }}</td>
                        <td>
                        @if(!is_null($user->leave_date))
                            <span class="mb_msg withdraw">탈퇴</span>
                        @elseif (!is_null($user->intercept_date))
                            <span class="mb_msg intercept">차단</span>
                        @else
                            <span class="mb_msg">정상</span>
                        @endif
                            <select id='level_{{ $user->id }}'>
                                @for ($i=1; $i<=10; $i++)
                                    <option value='{{ $i }}' {{ $user->level == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </td>
                        <td>{{ $user->point }}</td>
                        <td>
                            <div class="mb_tooltip">
                                @date($user->created_at)
                                <span class="tooltiptext">자세한날짜</span>
                            </div>
                        </td>
                        <td>
                            <div class="mb_tooltip">
                                @date($user->today_login)
                                <span class="tooltiptext">자세한날짜</span>
                            </div>
                        </td>
                        <td>
                            @if($user->count_groups > 0)
                                <a href="{{ route('admin.accessGroups.show', $user->id) }}">
                                    {{ $user->count_groups }}
                                </a>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.users.edit', $user->id) }}">수정</a>
                            <a href="{{ route('admin.accessGroups.show', $user->id) }}">그룹</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </form>

            {{-- 페이지 처리 --}}
            {{ str_contains(url()->current(), 'search')
                ? $users->appends([
                    'admin_page' => 'user',
                    'kind' => $kind,
                    'keyword' => $keyword,
                ])->links()
                : $users->links()
            }}
    </div>
</div>

<script>
var menuVal = 200100;
$(function(){
    // 선택 삭제 버튼 클릭
    $('#selected_delete').click(function(){
        var selected_id_array = selectIdsByCheckBox(".userId");

        if(selected_id_array.length == 0) {
            alert('회원을 선택해 주세요.');
            return;
        }

        $('#ids').val(selected_id_array);
        $('#_method').val('DELETE');
        <?php $ids=''; ?>
        $('#selectForm').attr('action', '{!! route('admin.users.destroy', $ids) !!}' + '/' + selected_id_array);
        $('#selectForm').submit();
    });

    // 선택 수정 버튼 클릭
    $('#selected_update').click(function(){

        var selected_id_array = selectIdsByCheckBox(".userId");

        if(selected_id_array.length == 0) {
            alert('회원을 선택해 주세요.')
            return;
        }

        var open_array = toUpdateByCheckBox("open", selected_id_array);
        var mailing_array = toUpdateByCheckBox("mailing", selected_id_array);
        var sms_array = toUpdateByCheckBox("sms", selected_id_array);
        var intercept_array = toUpdateByCheckBox("intercept_date", selected_id_array);
        var level_array = toUpdateBySelectOption("level", selected_id_array);

        $('#ids').val(selected_id_array);
        $('#opens').val(open_array);
        $('#mailings').val(mailing_array);
        $('#smss').val(sms_array);
        $('#intercepts').val(intercept_array);
        $('#levels').val(level_array);
        $('#_method').val('PUT');
        $('#selectForm').attr('action', '{!! route('admin.users.selectedUpdate') !!}');
        $('#selectForm').submit();
    });

});

function toUpdateByCheckBox(id, selected_id_array) {
    var send_array = Array();
    for(i=0; i<selected_id_array.length; i++) {
        var chkbox = $('input[id= ' + id + '_' + selected_id_array[i] + ']');
        if(chkbox.is(':checked')) {
            send_array[i] = chkbox.val();
        } else {
            send_array[i] = 0;
        }
    }

    return send_array;
}

function toUpdateBySelectOption(id, selected_id_array) {
    var send_array = Array();
    for(i=0; i<selected_id_array.length; i++) {
        send_array[i] = $('select[id=' + id + '_' + selected_id_array[i] + ']').val();
    }

    return send_array;
}
</script>
@endsection

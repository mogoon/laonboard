@extends('admin.admin')

@section('title')
    접근 가능 그룹 | {{ $config->title }}
@endsection

@section('include_script')
    <script src="{{ asset('js/common.js') }}"></script>
@endsection

@section('content')
<div class="body-head">
    <div class="pull-left">
        <h3>접근가능그룹</h3>
        <ul class="fl">
            <li class="admin">Admin</li>
            <li class="depth">회원관리</li>
            <li class="depth">회원수정</li>
        </ul>
    </div>
</div>
<div id="body_tab_type2">
    <span class="txt">{{ $user->nick }} 님의 접근 가능 그룹을 지정합니다.</span>
</div>

<div class="body-contents">
    @if(Session::has('message'))
        <div id="adm_save">
            <span class="adm_save_txt">{{ Session::get('message') }}</span>
            <button onclick="alertclose()" class="adm_alert_close">
                <i class="fa fa-times"></i>
            </button>
        </div>
    @endif
    <div id="board">
        
                
                <form class="form-horizontal" role="form" method="POST" action="{{ route('admin.accessGroups.store') }}">
                    <input type="hidden" name="user_id" value="{{ $user->id }}" />
                    {{ csrf_field() }}
                    <span class="total">
                        이메일 <b>{{ $user->email }}</b>, 닉네임 <b>{{ $user->nick }}</b>@if(!is_null($user->name)), 이름 <b>{{ $user->name }}</b> @endif
                    </span>
                    <p style="clear: both;">
                        그룹지정
                        <select name="group_id">
                            <option>접근가능 그룹을 선택하세요.</option>
                            @foreach($accessible_groups as $accessible_group)
                                <option value="{{ $accessible_group->id }}">{{ $accessible_group->subject }}</option>
                            @endforeach
                        </select>
                        <input type="submit" class="btn btn-primary" value="선택" />
                    </p>
                </form>
                <form class="form-horizontal" role="form" method="POST" id="selectForm" action="">
                    <input type="hidden" id='ids' name='ids' value='' />
                    <input type="hidden" id='_method' name='_method' value='' />
                    <div class="panel-body">
                        {{ csrf_field() }}
                        <table class="table table-striped box">
                            <thead>
                                <th class="text-center"><input type="checkbox" name="chkAll" onclick="checkAll(this.form)"/></th>
                                <th class="text-center">그룹아이디</th>
                                <th class="text-center">그룹</th>
                                <th class="text-center">처리일시</th>
                            </thead>

                            <tbody>
                            @foreach ($groups as $group)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="chkId[]" class="groupId" value='{{ $group->pivot->id }}' /></td>
                                    <td class="text-center">{{ $group->group_id }}</td>
                                    <td class="text-center">{{ $group->subject }}</td>
                                    <td class="text-center">{{ $group->pivot->created_at }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-heading">
                        <input type="button" id="selected_delete" class="btn btn-primary" value="선택 삭제"/>
                    </div>
                </form>

    </div>
</div>
<script>
var menuVal = 200100;
$(function(){
    // 선택 삭제 버튼 클릭
    $('#selected_delete').click(function(){
        var selected_id_array = selectIdsByCheckBox(".groupId");

        if(selected_id_array.length == 0) {
            alert('게시판 그룹을 선택해 주세요.')
            return;
        }

        $('#ids').val(selected_id_array);
        $('#_method').val('DELETE');
        $('#selectForm').attr('action', '/admin/accessible_groups' + '/' + {{ $user->id}});
        $('#selectForm').submit();
    });

});
</script>
@endsection

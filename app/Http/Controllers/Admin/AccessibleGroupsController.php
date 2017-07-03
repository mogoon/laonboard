<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Admin\GroupUser;

// 접근 가능 그룹
class AccessibleGroupsController extends Controller
{

    public $groupUserModel;

    public function __construct(GroupUser $groupUser)
    {
        $this->groupUserModel = $groupUser;
    }

    /**
     * Display the specified resource.
     * 리스트 보여주기
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (auth()->user()->cant('index', $this->groupUserModel)) {
            abort(403, '접근 가능 그룹 목록 보기에 대한 권한이 없습니다.');
        }

        $params = $this->groupUserModel->getAccessibleGroups($id);

        return view('admin.group_user.accessible_group_list', $params);
    }

    /**
     * Store a newly created resource in storage.
     * 선택 버튼으로 넘어오는 폼 저장
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (auth()->user()->cant('create', GroupUser::class)) {
            abort(403, '접근 가능 그룹 추가에 대한 권한이 없습니다.');
        }

        $message = $this->groupUserModel->addAccessibleGroups($request);

        return redirect(route('admin.accessGroups.show', $request->get('user_id')))
            ->with('message', $message);
    }

    /**
     * Remove the specified resource from storage.
     * 선택 삭제
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (auth()->user()->cant('delete', $this->groupUserModel)) {
            abort(403, '접근 가능 그룹 삭제에 대한 권한이 없습니다.');
        }

        $message = $this->groupUserModel->delAccessibleGroups($request);
        return redirect(route('admin.accessGroups.show', $id))
            ->with('message', $message);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Common\Util;
use App\Admin\Config;
use App\Board;
use App\Content;
use File;

class ThemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('super');
    }
    public function index()
    {
        $layoutSkins = Util::getSkins('layout');
        $themes = $this->getThemeList($layoutSkins);
        $boardSkins = Util::getSkins('board');
        $userSkins = Util::getSkins('user');
        $latestSkins = Util::getSkins('latest');
        $newSkins = Util::getSkins('new');
        $searchSkins = Util::getSkins('search');
        $contentSkins = Util::getSkins('content');
        $mailSkins = Util::getSkins('mail');
        $memoSkins = Util::getSkins('memo');

        $params = [
            'themes' => $themes,
            'layoutSkins' => $layoutSkins,
            'boardSkins' => $boardSkins,
            'userSkins' => $userSkins,
            'latestSkins' => $latestSkins,
            'newSkins' => $newSkins,
            'searchSkins' => $searchSkins,
            'contentSkins' => $contentSkins,
            'mailSkins' => $mailSkins,
            'memoSkins' => $memoSkins,
        ];

        return view('admin.configs.theme', $params);
    }

    // basic, main 이 있는 layout 스킨만을 테마로 뽑아 온다. + 정렬
    private function getThemeList($themes)
    {
        foreach($themes as $key => $value) {
            $path = resource_path('views/layout/'.$key);
            if( !File::isFile("$path/basic.blade.php") || !File::isFile("$path/main.blade.php") ) {
                $themes = array_except($themes, $key);
            }
            if( $key == '') {
                $themes = array_except($themes, $key);
            }
        }
        $result = [];
        // 설정된 테마가 맨 앞에 오도록 정렬하기 위한 로직
        foreach($themes as $key => $value) {
            if($key == cache('config.theme')->name) {
                $themes[$key] = 1;
            } else {
                $themes[$key] = 0;
            }
            $result[] = [
                'name' => $key,
                'use' => $themes[$key],
                'info' => $this->getThemeInfo($key)
            ];
        }
        // 정렬
        $result = collect($result);
        $sorted = $result->sortByDesc('use');
        $result = $sorted->values()->all();

        return $result;
    }

    private function getThemeInfo($theme)
    {
        $info = [];
        $path = resource_path('views/layout/'.$theme);
        $text = $path.'/readme.txt';
        if(is_file($text)) {
            $content = file($text, false);
            $content = array_map('trim', $content);

            preg_match('#^Theme Name:(.+)$#i', $content[0], $m0);
            preg_match('#^Theme URI:(.+)$#i', $content[1], $m1);
            preg_match('#^Maker:(.+)$#i', $content[2], $m2);
            preg_match('#^Maker URI:(.+)$#i', $content[3], $m3);
            preg_match('#^Version:(.+)$#i', $content[4], $m4);
            preg_match('#^Detail:(.+)$#i', $content[5], $m5);
            preg_match('#^License:(.+)$#i', $content[6], $m6);
            preg_match('#^License URI:(.+)$#i', $content[7], $m7);

            $info['themeName'] = trim($m0[1]);
            $info['themeUri'] = trim($m1[1]);
            $info['maker'] = trim($m2[1]);
            $info['makerUri'] = trim($m3[1]);
            $info['version'] = trim($m4[1]);
            $info['detail'] = trim($m5[1]);
            $info['license'] = trim($m6[1]);
            $info['licenseUri'] = trim($m7[1]);
        }

        if( !isset($info['themeName']) ) {
            $info['themeName'] = $theme;
        }

        return $info;
    }

    // 테마 변경
    public function update(Request $request)
    {
        $config = new Config();
        // 테마 변경
        $theme = $request->has('theme') ? $request->theme : 'default';
        $data = ['name' => $theme];
        $config->updateConfig($data, 'theme', 1);

        // 모든 스킨의 theme 값을 변경 : 해당 항목의 스킨이 존재 하지 않으면 변경하지 않음.
        // 게시판별 스킨 변경
        if($this->hasSkin('board', $theme)) {
            foreach(Board::cursor() as $board) {
                $board->skin = $theme;
                $board->save();
            }
            $data = ['board' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 내용별 스킨 변경
        if($this->hasSkin('content', $theme)) {
            foreach(Content::cursor() as $content) {
                $content->skin = $theme;
                $content->save();
            }
            $data = ['content' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 최신 게시물(메인에 노출되는) 스킨 변경
        if($this->hasSkin('latest', $theme)) {
            $data = ['latestSkin' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 홈페이지 레이아웃 스킨 변경
        if($this->hasSkin('layout', $theme)) {
            $data = ['layout' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 메일 양식 스킨 변경
        if($this->hasSkin('mail', $theme)) {
            $data = ['mail' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 쪽지 스킨 변경
        if($this->hasSkin('memo', $theme)) {
            $data = ['memo' => $theme];
            $config->updateConfig($data, 'skin', 1);
        }
        // 새글 스킨 변경
        if($this->hasSkin('new', $theme)) {
            $data = ['newSkin' => $theme];
            $config->updateConfig($data, 'homepage', 1);
        }
        // 전체 검색 스킨 변경
        if($this->hasSkin('search', $theme)) {
            $data = ['searchSkin' => $theme];
            $config->updateConfig($data, 'homepage', 1);
        }
        // 회원/로그인 스킨 변경
        if($this->hasSkin('user', $theme)) {
            $data = ['skin' => $theme];
            $config->updateConfig($data, 'join', 1);
        }

    }

    // 해당 항목에 스킨이 있는지 조사
    private function hasSkin($type, $name)
    {
        $skins = Util::getSkins($type);
        return in_array(strtolower($name), $skins);
    }

    // 개별 스킨 변경
    public function updateSkin(Request $request)
    {
        $config = new Config();
        // 게시판별 스킨 변경
        foreach(Board::cursor() as $board) {
            $board->skin = $request->boardSkin ? : 'default';
            $board->save();
        }
        $config->updateConfig(['board' => $request->boardSkin ? : 'default'], 'skin', 1);
        // 내용별 스킨 변경
        foreach(Content::cursor() as $content) {
            $content->skin = $request->contentSkin ? : 'default';
            $content->save();
        }
        $config->updateConfig(['content' => $request->contentSkin ? : 'default'], 'skin', 1);
        // 최신 게시물(메인에 노출되는) 스킨 변경
        $config->updateConfig(['latestSkin' => $request->latestSkin ? : 'default'], 'skin', 1);
        // 홈페이지 레이아웃 스킨 변경
        $config->updateConfig(['layout' => $request->layoutSkin ? : 'default'], 'skin', 1);
        // 메일 양식 스킨 변경
        $config->updateConfig(['mail' => $request->mailSkin ? : 'default'], 'skin', 1);
        // 쪽지 스킨 변경
        $config->updateConfig(['memo' => $request->memoSkin ? : 'default'], 'skin', 1);
        // 새글 스킨 변경
        $config->updateConfig(['newSkin' => $request->newSkin ? : 'default'], 'homepage', 1);
        // 전체 검색 스킨 변경
        $config->updateConfig(['searchSkin' => $request->searchSkin ? : 'default'], 'homepage', 1);
        // 회원/로그인 스킨 변경
        $config->updateConfig(['skin' => $request->userSkin ? : 'default'], 'join', 1);

        return redirect()->back();
    }

    // 테마 상세 보기
    public function detail(Request $request)
    {
        $theme = $request->theme ? : 'default';
        $info = $this->getThemeInfo($theme);
        $info['themeName'] = Util::getText($info['themeName']);
        $info['maker'] = isset($info['maker']) ? Util::getText($info['maker']) : '';
        $info['license'] = isset($info['license']) ? Util::getText($info['license']) : '';
        $info['version'] = isset($info['version']) ? Util::getText($info['version']) : '';
        $info['detail'] = isset($info['detail']) ? Util::getText($info['detail']) : '';

        return view('admin.configs.theme_detail', [
            'theme' => $theme,
            'info' => $info
        ]);
    }
}
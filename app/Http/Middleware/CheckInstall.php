<?php

namespace App\Http\Middleware;

use Closure;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CheckInstall
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $file = '.env';
        $path = base_path($file);
        try {
            if(File::exists($path) && env('DB_DATABASE') && env('DB_USERNAME') && env('DB_PASSWORD') && env('DB_PREFIX')) {
                // DB 연결 확인
                DB::getPdo();
                // 기본 설정 테이블 존재 확인
                if(!Schema::hasTable('configs')) {
                    return view('install.index');
                }
                return $next($request);
            } else {
                return view('install.index');
            }
        } catch (PDOException $e) {
            dd('Database Connect Error!!!');
        }

    }
}

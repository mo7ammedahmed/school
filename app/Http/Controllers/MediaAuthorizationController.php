<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learning\Services\LiveMediaAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class MediaAuthorizationController extends Controller
{
    public function __invoke(Request $request, LiveMediaAccess $access): Response
    {
        $action = $request->input('action');
        if ($action === 'api') {
            $user = (string) config('media.api_user');
            $password = (string) config('media.api_password');
            abort_unless($user !== '' && $password !== ''
                && is_string($request->input('user')) && is_string($request->input('password'))
                && hash_equals($user, $request->input('user'))
                && hash_equals($password, $request->input('password')), 403);

            return response()->noContent();
        }

        $token = $request->input('token');
        $path = $request->input('path');
        abort_unless(is_string($token) && strlen($token) <= 4096
            && is_string($path) && is_string($action)
            && $access->accepts($token, $path, $action), 403);

        return response()->noContent();
    }
}

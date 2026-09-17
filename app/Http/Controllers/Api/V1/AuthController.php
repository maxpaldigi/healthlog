<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueApiTokenFromMagicLink;
use App\Actions\Auth\SendMagicLink;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendMagicLinkRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function magicLink(SendMagicLinkRequest $request, SendMagicLink $sendMagicLink): JsonResponse
    {
        $dispatch = $sendMagicLink->handle($request->validated('email'));

        $payload = [
            'message' => 'If that email is registered, a login link is on its way.',
        ];

        // Local/log mailers only — never returned when real mail is configured.
        if ($dispatch->devLoginUrl !== null) {
            $payload['login_url'] = $dispatch->devLoginUrl;
        }

        if ($dispatch->mailFailed) {
            $payload['mail_failed'] = true;
        }

        return response()->json($payload);
    }

    public function token(Request $request, IssueApiTokenFromMagicLink $issue): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:32', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:2048'],
        ]);

        $plainToken = $data['token'];

        if (isset($data['url']) && is_string($data['url']) && $data['url'] !== '') {
            $parts = parse_url($data['url']);
            $path = $parts['path'] ?? '';

            if (preg_match('#/login/magic/([^/?]+)#', $path, $matches) === 1) {
                $plainToken = rawurldecode($matches[1]);
            }
        }

        $result = $issue->handle($plainToken, $data['device_name'] ?? 'flutter');

        if ($result === null) {
            return response()->json([
                'message' => 'This login link is invalid or has expired.',
            ], 422);
        }

        $user = $result['user'];

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'health_data_consent' => $user->hasGrantedConsent(ConsentType::HealthData),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        Auth::forgetGuards();

        return response()->json(['message' => 'Logged out.']);
    }
}

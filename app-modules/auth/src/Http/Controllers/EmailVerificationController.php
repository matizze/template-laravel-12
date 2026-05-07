<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailVerificationController extends Controller
{
    /**
     * Verify the user's email via signed URL.
     *
     * The route is public (no auth) because the user clicks a link from their inbox.
     * Authenticity is enforced by the `signed` middleware (validates Laravel's
     * signature against the URL parameters and expiry) plus a hash comparison
     * against the user's current email.
     */
    public function verify(int $id, string $hash): JsonResponse
    {
        $user = User::query()->find($id);

        if (! $user instanceof User || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new AccessDeniedHttpException('Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'verified' => true,
            ]);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return response()->json([
            'message' => 'Email verified.',
            'verified' => true,
        ]);
    }

    /**
     * Resend the email verification notification for the authenticated user.
     */
    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'verified' => true,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }
}

<?php
namespace SudGipAuth\Controllers;

use Sud\Gip\Api\SudGIPAuth;
use SudGipAuth\Http\Response;

class AuthController
{
    private $auth;

    public function __construct(SudGIPAuth $auth)
    {
        $this->auth = $auth;
    }

    public function getCode($params)
    {
        $uid = $params['uid'] ?? null;
        if (!is_string($uid) || trim($uid) === '') {
            $this->sendErrorResponse(1, "Missing uid parameter");
            return;
        }

        try {
            $codeObj = $this->auth->getCode($uid);
            $this->sendSuccessResponse([
                "code" => $codeObj->code
            ]);
        } catch (\Throwable $e) {
            Response::error(500, 'Internal Server Error');
        }
    }

    public function getSSToken($body)
    {
        $code = $body['code'] ?? null;
        if (!is_string($code) || trim($code) === '') {
            $this->sendErrorResponse(1, "Missing code parameter");
            return;
        }

        $uidResponse = $this->auth->getUidByCode($code);
        if (!$uidResponse->isSuccess) {
            $this->sendErrorResponse(1, "", $uidResponse->errorCode);
            return;
        }

        try {
            $uid = $uidResponse->uid;
            if (!is_string($uid) || trim($uid) === '') {
                $this->sendErrorResponse(1, 'Missing token uid', 1004);
                return;
            }
            $sstokenObj = $this->auth->getSSToken($uid);

            $this->sendSuccessResponse([
                "ss_token" => $sstokenObj->token,
                "expire_date" => $sstokenObj->expireDate,
                // Profile data is mocked here; only the UID comes from the verified token.
                "user_info" => [
                    "uid" => $uid,
                    "nick_name" => "Demo User",
                    "avatar_url" => "https://example.com/avatar.png",
                    "gender" => "female",
                    "is_ai" => 0,
                    "ai_level" => 0
                ]
            ], 0);
        } catch (\Throwable $e) {
            Response::error(500, 'Internal Server Error');
        }
    }

    public function updateSSToken($body)
    {
        $ssToken = $body['ss_token'] ?? null;
        if (!is_string($ssToken) || trim($ssToken) === '') {
            $this->sendErrorResponse(1, "Missing ss_token parameter");
            return;
        }

        $uidResponse = $this->auth->getUidBySSToken($ssToken);
        if (!$uidResponse->isSuccess) {
            $this->sendErrorResponse(1, "", $uidResponse->errorCode);
            return;
        }

        try {
            $uid = $uidResponse->uid;
            if (!is_string($uid) || trim($uid) === '') {
                $this->sendErrorResponse(1, 'Missing token uid', 1004);
                return;
            }
            $newSSTokenObj = $this->auth->getSSToken($uid);

            $this->sendSuccessResponse([
                "ss_token" => $newSSTokenObj->token,
                "expire_date" => $newSSTokenObj->expireDate
            ], 0);
        } catch (\Throwable $e) {
            Response::error(500, 'Internal Server Error');
        }
    }

    private function sendSuccessResponse($data, $sdkErrorCode = 0): void
    {
        Response::success($data);
    }

    private function sendErrorResponse($retCode, $retMsg, $sdkErrorCode = 0): void
    {
        Response::error(400, $retMsg, $sdkErrorCode);
    }
}

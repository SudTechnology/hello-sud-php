<?php
namespace SudGipAuth\Controllers;

use Sud\Gip\Api\SudGIPAuth;

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
        if (!$uid) {
            $this->sendErrorResponse(1, "Missing uid parameter");
            return;
        }
        
        try {
            $codeObj = $this->auth->getCode($uid);
            $this->sendSuccessResponse([
                "code" => $codeObj->code
            ]);
        } catch (Exception $e) {
            $this->sendErrorResponse(1, $e->getMessage());
        }
    }

    public function getSSToken($body)
    {
        $code = $body['code'] ?? null;
        if (!$code) {
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
            $sstokenObj = $this->auth->getSSToken($uid);
            
            $this->sendSuccessResponse([
                "ss_token" => $sstokenObj->token,
                "expire_date" => $sstokenObj->expireDate,
                "user_info" => [
                    "uid" => $uid,
                    "nick_name" => "萌萌",
                    "avatar_url" => "https://icon.png?128*128",
                    "gender" => "female",
                    "is_ai" => 0,
                    "ai_level" => 0
                ]
            ], 0);
        } catch (Exception $e) {
            $this->sendErrorResponse(1, $e->getMessage());
        }
    }

    public function updateSSToken($body)
    {
        $ssToken = $body['ss_token'] ?? null;
        if (!$ssToken) {
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
            $newSSTokenObj = $this->auth->getSSToken($uid);
            
            $this->sendSuccessResponse([
                "ss_token" => $newSSTokenObj->token,
                "expire_date" => $newSSTokenObj->expireDate
            ], 0);
        } catch (Exception $e) {
            $this->sendErrorResponse(1, $e->getMessage());
        }
    }

    private function sendSuccessResponse($data, $sdkErrorCode = 0)
    {
        echo json_encode([
            "ret_code" => 0,
            "ret_msg" => "",
            "sdk_error_code" => $sdkErrorCode,
            "data" => $data
        ]);
    }

    private function sendErrorResponse($retCode, $retMsg, $sdkErrorCode = 0)
    {
        http_response_code(400);
        echo json_encode([
            "ret_code" => $retCode,
            "ret_msg" => $retMsg,
            "sdk_error_code" => $sdkErrorCode,
            "data" => []
        ]);
    }
}    
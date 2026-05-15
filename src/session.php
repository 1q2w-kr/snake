<?php

final class FunSession
{
    public static function resolve(): array
    {
        $empty = [
            'loggedIn' => false,
            'memberSrl' => null,
            'userId' => null,
            'nickname' => null,
            'isAdmin' => false,
        ];

        if (function_exists('g7CurrentUser')) {
            $user = g7CurrentUser();
            return [
                'loggedIn' => !empty($user['loggedIn']),
                'memberSrl' => $user['memberSrl'] ?? ($user['memberId'] ?? null),
                'userId' => $user['userId'] ?? null,
                'nickname' => $user['nickname'] ?? ($user['displayName'] ?? null),
                'isAdmin' => !empty($user['isAdmin']),
            ];
        }

        if (!isset($_SESSION) || empty($_SESSION['gnuboard_logged_info'])) {
            return $empty;
        }

        $info = $_SESSION['gnuboard_logged_info'];
        $data = is_object($info) ? get_object_vars($info) : (is_array($info) ? $info : []);

        return [
            'loggedIn' => !empty($data),
            'memberSrl' => $data['member_srl'] ?? ($data['member_id'] ?? null),
            'userId' => $data['user_id'] ?? null,
            'nickname' => $data['nick_name'] ?? ($data['nickname'] ?? null),
            'isAdmin' => ($data['is_admin'] ?? 'N') === 'Y' || ($data['is_admin'] ?? false) === true,
        ];
    }
}

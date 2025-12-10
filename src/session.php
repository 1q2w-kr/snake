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

        if (!isset($_SESSION) || empty($_SESSION['rhymix_logged_info'])) {
            return $empty;
        }

        $info = $_SESSION['rhymix_logged_info'];

        return [
            'loggedIn' => true,
            'memberSrl' => $info->member_srl ?? null,
            'userId' => $info->user_id ?? null,
            'nickname' => $info->nick_name ?? null,
            'isAdmin' => ($info->is_admin ?? 'N') === 'Y',
        ];
    }
}

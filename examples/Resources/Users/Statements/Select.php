<?php
namespace Resources\Users\Statements;

class Select {
    public static $all =
        "
        select
            *
        from
            users
        ";

    public static $emailById =
        "
        select
            email
        from
            users
        where
            user_id = :0
        ";
}

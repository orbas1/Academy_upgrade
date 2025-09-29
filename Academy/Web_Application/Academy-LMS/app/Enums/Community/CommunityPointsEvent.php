<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityPointsEvent: string
{
    case POST = 'post';
    case COMMENT = 'comment';
    case LIKE_RECEIVED = 'like_received';
    case LOGIN_STREAK = 'login_streak';
    case COURSE_COMPLETE = 'course_complete';
    case ASSIGNMENT_SUBMIT = 'assignment_submit';

    public function label(): string
    {
        return match ($this) {
            self::POST => 'communities.points.events.post',
            self::COMMENT => 'communities.points.events.comment',
            self::LIKE_RECEIVED => 'communities.points.events.like_received',
            self::LOGIN_STREAK => 'communities.points.events.login_streak',
            self::COURSE_COMPLETE => 'communities.points.events.course_complete',
            self::ASSIGNMENT_SUBMIT => 'communities.points.events.assignment_submit',
        };
    }
}

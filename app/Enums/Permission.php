<?php

namespace App\Enums;

/**
 * [REVIEW-FIX] C1: 权限名称常量注册表
 *
 * 统一管理所有 Spatie Permission 的权限名称，替代散落在 10+ 个文件中的裸字符串。
 * 用法: auth()->user()->can(Permission::MANAGE_TICKETS)
 */
final class Permission
{
    // ITSM
    public const VIEW_TICKETS      = 'view tickets';
    public const MANAGE_TICKETS    = 'manage tickets';
    public const VIEW_ASSETS       = 'view assets';
    public const MANAGE_ASSETS     = 'manage assets';
    public const VIEW_KNOWLEDGE    = 'view knowledge';
    public const EDIT_KNOWLEDGE    = 'edit knowledge';
    public const VIEW_CHANGES      = 'view changes';
    public const APPROVE_CHANGES   = 'approve changes';
    public const VIEW_INCIDENTS    = 'view incidents';
    public const MANAGE_INCIDENTS  = 'manage incidents';
    public const VIEW_SLAS         = 'view slas';
    public const MANAGE_SLAS       = 'manage slas';

    // Project
    public const VIEW_PROJECTS     = 'view projects';
    public const CREATE_PROJECTS   = 'create projects';
    public const EDIT_PROJECTS     = 'edit projects';
    public const DELETE_PROJECTS   = 'delete projects';
    public const ASSIGN_MEMBERS    = 'assign project members';
    public const VIEW_ALL_PROJECTS = 'view all projects';

    // Category
    public const VIEW_CATEGORIES   = 'view categories';
    public const CREATE_CATEGORIES = 'create categories';
    public const EDIT_CATEGORIES   = 'edit categories';
    public const DELETE_CATEGORIES = 'delete categories';

    // System
    public const VIEW_USERS        = 'view users';
    public const CREATE_USERS      = 'create users';
    public const EDIT_USERS        = 'edit users';
    public const DELETE_USERS      = 'delete users';
    public const MANAGE_ROLES      = 'manage roles';
    public const UPLOAD_ATTACHMENTS = 'upload attachments';
    public const DELETE_ATTACHMENTS = 'delete attachments';

    /** @return string[] */
    public static function all(): array
    {
        return (new \ReflectionClass(self::class))->getConstants();
    }
}

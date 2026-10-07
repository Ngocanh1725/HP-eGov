<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;

/**
 * Class Auth (Core Facade)
 * Cung cấp truy cập nhanh đến các hàm xác thực và phân quyền RBAC
 */
class Auth
{
    public static function check(?string $guard = null): bool
    {
        return AuthService::check($guard);
    }

    public static function guard(): ?string
    {
        return AuthService::guard();
    }

    public static function user(): ?array
    {
        return AuthService::user();
    }

    public static function id(): ?int
    {
        return AuthService::id();
    }

    public static function roles(): array
    {
        return AuthService::roles();
    }

    public static function hasRole(string|array $role): bool
    {
        return AuthService::hasRole($role);
    }

    public static function permissions(): array
    {
        return AuthService::permissions();
    }

    public static function can(string $permissionCode): bool
    {
        return AuthService::can($permissionCode);
    }

    public static function currentDonVi(): ?array
    {
        return AuthService::currentDonVi();
    }

    public static function donViList(): array
    {
        return AuthService::donViList();
    }

    public static function switchDonVi(int $donViId): bool
    {
        return AuthService::switchDonVi($donViId);
    }

    public static function logout(): void
    {
        AuthService::logout();
    }
}

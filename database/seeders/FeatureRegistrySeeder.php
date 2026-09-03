<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeatureRegistry;
use App\Models\User;

class FeatureRegistrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $qaUser = User::where('email', 'qa@regradar.com')->first();

        $features = [
            [
                'feature_name' => 'User Authentication',
                'code_areas' => ['AuthController', 'LoginRequest', 'UserService', 'TokenHelper'],
                'endpoints' => ['/api/auth/login', '/api/auth/logout', '/api/auth/token'],
                'description' => 'Handles user login, logout, and token generation',
            ],
            [
                'feature_name' => 'Password Reset',
                'code_areas' => ['PasswordResetController', 'ResetPasswordMail', 'PasswordBroker'],
                'endpoints' => ['/api/password/forgot', '/api/password/reset'],
                'description' => 'Forgot password and password reset flow',
            ],
            [
                'feature_name' => 'User Profile Management',
                'code_areas' => ['ProfileController', 'UpdateProfileRequest', 'UserRepository'],
                'endpoints' => ['/api/profile', '/api/profile/update', '/api/profile/avatar'],
                'description' => 'User profile view and update functionality',
            ],
            [
                'feature_name' => 'Role & Permission Management',
                'code_areas' => ['RoleController', 'PermissionService', 'RoleMiddleware'],
                'endpoints' => ['/api/roles', '/api/permissions', '/api/roles/assign'],
                'description' => 'User roles, permissions, and access control',
            ],
            [
                'feature_name' => 'Email Notifications',
                'code_areas' => ['NotificationService', 'MailController', 'EmailTemplate'],
                'endpoints' => ['/api/notifications', '/api/notifications/settings'],
                'description' => 'System email notifications and notification preferences',
            ],
        ];

        foreach ($features as $feature) {
            FeatureRegistry::create(array_merge($feature, ['user_id' => $qaUser->id]));
        }
    }
}

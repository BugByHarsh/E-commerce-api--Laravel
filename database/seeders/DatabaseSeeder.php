<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create test users
        $this->createUsers();

        // Seed categories
        $this->call(CategorySeeder::class);

        // Seed products
        $this->call(ProductSeeder::class);

        // Seed sample orders
        $this->call(OrderSeeder::class);

        $this->command->info('====================================');
        $this->command->info('Database seeding completed successfully!');
        $this->command->info('====================================');
        $this->command->info('');
        $this->command->info('Test Credentials:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password123');
        $this->command->info('');
        $this->command->info('Admin Credentials:');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password123');
    }

    private function createUsers()
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create regular user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create additional users
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Mike Johnson', 'email' => 'mike@example.com'],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
            ]);
        }

        $this->command->info('Users created: '.User::count());
    }
}

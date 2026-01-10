<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid constraint violations during seed
        Schema::disableForeignKeyConstraints();
        
        DB::table('users')->truncate();
        DB::table('companies')->truncate();
        DB::table('students')->truncate();
        DB::table('daily_time_records')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('announcements')->truncate();

        $this->command->info('Migrating Users...');
        $oldUsers = DB::connection('legacy')->table('users')->get();
        foreach ($oldUsers as $oldUser) {
            DB::table('users')->insert([
                'id' => $oldUser->user_id,
                'username' => $oldUser->username,
                'name' => $oldUser->username, // Fallback
                'email' => $oldUser->email,
                'password' => $oldUser->password_hash,
                'user_type' => $oldUser->user_type,
                'is_active' => $oldUser->is_active,
                'profile_picture' => $oldUser->profile_picture,
                'created_at' => $oldUser->created_at,
                'updated_at' => $oldUser->updated_at,
            ]);
        }

        $this->command->info('Migrating Companies...');
        $oldCompanies = DB::connection('legacy')->table('companies')->get();
        foreach ($oldCompanies as $oldComp) {
            DB::table('companies')->insert([
                'id' => $oldComp->company_id,
                'user_id' => $oldComp->user_id,
                'company_name' => $oldComp->company_name,
                'address' => $oldComp->address,
                'supervisor_name' => $oldComp->supervisor_name,
                'contact_number' => $oldComp->contact_number,
                'email' => $oldComp->email,
                'created_at' => $oldComp->created_at,
                'updated_at' => $oldComp->updated_at,
            ]);
        }

        $this->command->info('Migrating Students...');
        $oldStudents = DB::connection('legacy')->table('students')->get();
        foreach ($oldStudents as $oldStud) {
            DB::table('students')->insert([
                'id' => $oldStud->student_id,
                'user_id' => $oldStud->user_id,
                'first_name' => $oldStud->first_name,
                'last_name' => $oldStud->last_name,
                'middle_name' => $oldStud->middle_name,
                'course' => $oldStud->course,
                'year_level' => $oldStud->year_level,
                'contact_number' => $oldStud->contact_number,
                'email_address' => $oldStud->email_address,
                'address' => $oldStud->address,
                'company_id' => $oldStud->company_id,
                'created_at' => $oldStud->created_at,
                'updated_at' => $oldStud->updated_at,
            ]);
            
            // Update user name with real name
            DB::table('users')->where('id', $oldStud->user_id)->update([
                'name' => $oldStud->first_name . ' ' . $oldStud->last_name
            ]);
        }

        $this->command->info('Migrating Daily Time Records...');
        $oldDTRs = DB::connection('legacy')->table('daily_time_records')->get();
        foreach ($oldDTRs as $dtr) {
            DB::table('daily_time_records')->insert([
                'id' => $dtr->dtr_id,
                'student_id' => $dtr->student_id,
                'record_date' => $dtr->record_date,
                'time_in' => $dtr->time_in,
                'time_out' => $dtr->time_out,
                'daily_hours' => $dtr->daily_hours,
                'status' => $dtr->status,
                'notes' => $dtr->notes,
                'created_at' => $dtr->created_at,
                'updated_at' => $dtr->updated_at,
            ]);
        }

        $this->command->info('Migrating Activity Logs...');
        $oldLogs = DB::connection('legacy')->table('activity_logs')->get();
        foreach ($oldLogs as $log) {
            DB::table('activity_logs')->insert([
                'id' => $log->activity_id,
                'student_id' => $log->student_id,
                'week_starting' => $log->week_starting,
                'week_ending' => $log->week_ending,
                'task_description' => $log->task_description,
                'hours_rendered' => $log->hours_rendered,
                'accomplishments' => $log->accomplishments,
                'status' => $log->status,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ]);
        }

        $this->command->info('Migrating Announcements...');
        $oldAnns = DB::connection('legacy')->table('announcements')->get();
        foreach ($oldAnns as $ann) {
            DB::table('announcements')->insert([
                'id' => $ann->announcement_id,
                'admin_id' => $ann->admin_id,
                'title' => $ann->title,
                'content' => $ann->content,
                'announcement_type' => $ann->announcement_type,
                'posted_at' => $ann->posted_at,
                'scheduled_date' => $ann->scheduled_date,
                'is_active' => $ann->is_active,
                'company_id' => $ann->company_id,
                'created_at' => $ann->created_at,
                'updated_at' => $ann->updated_at,
            ]);
        }

        Schema::enableForeignKeyConstraints();
        $this->command->info('Legacy Migration Completed!');
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Roles
    |--------------------------------------------------------------------------
    |
    | Define all valid user roles in the application.
    | These values are stored as strings in the database for PostgreSQL compatibility.
    |
    */

    'valid_roles' => [
        'student',
        'admin',
        'recruiter',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The default role assigned to new users.
    |
    */

    'default' => 'student',

    /*
    |--------------------------------------------------------------------------
    | Application Status Values
    |--------------------------------------------------------------------------
    |
    | Valid status values for internship applications.
    |
    */

    'application_statuses' => [
        'pending',
        'under_review',
        'shortlisted',
        'interview_scheduled',
        'approved',
        'rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Recruiter Approval Status Values
    |--------------------------------------------------------------------------
    |
    | Valid approval status values for recruiter profiles.
    |
    */

    'approval_statuses' => [
        'pending',
        'approved',
        'rejected',
        'suspended',
    ],
];

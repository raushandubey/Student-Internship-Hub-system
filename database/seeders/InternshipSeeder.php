<?php

namespace Database\Seeders;

use App\Models\Internship;
use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    public function run()
    {
        // Clear existing internships
        Internship::truncate();
        
        $workTypes = ['Remote', 'On-site', 'Hybrid'];
        $locations = [
            'Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Chennai', 'Pune', 'Kolkata', 'Ahmedabad',
            'Surat', 'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Indore', 'Thane', 'Bhopal',
            'Visakhapatnam', 'Pimpri-Chinchwad', 'Patna', 'Vadodara', 'Ghaziabad', 'Ludhiana',
            'Agra', 'Nashik', 'Faridabad', 'Meerut', 'Rajkot', 'Kalyan-Dombivali', 'Vasai-Virar',
            'Varanasi', 'Srinagar', 'Aurangabad', 'Dhanbad', 'Amritsar', 'Navi Mumbai', 'Allahabad',
            'Ranchi', 'Howrah', 'Coimbatore', 'Jabalpur', 'Gwalior', 'Vijayawada', 'Jodhpur',
            'Madurai', 'Raipur', 'Kota', 'Chandigarh', 'Gurgaon', 'Noida', 'Guwahati', 'Dehradun'
        ];
        
        $internships = [
            [
                'title' => 'Full Stack Developer Intern',
                'organization' => 'TechCorp Solutions',
                'required_skills' => ['React', 'Node.js', 'MongoDB', 'Express.js'],
                'duration' => '6 months',
                'location' => 'Bangalore',
                'work_type' => 'Hybrid',
                'description' => 'Develop end-to-end web applications using MERN stack.',
                'category' => 'Technology'
            ],
            [
                'title' => 'Python Developer Intern',
                'organization' => 'DataFlow Systems',
                'required_skills' => ['Python', 'Django', 'PostgreSQL', 'REST APIs'],
                'duration' => '4 months',
                'location' => 'Mumbai',
                'work_type' => 'Remote',
                'description' => 'Build scalable backend applications using Python and Django.',
                'category' => 'Technology'
            ],
            [
                'title' => 'React Native Developer Intern',
                'organization' => 'MobileFirst Technologies',
                'required_skills' => ['React Native', 'JavaScript', 'Firebase', 'Redux'],
                'duration' => '5 months',
                'location' => 'Hyderabad',
                'work_type' => 'On-site',
                'description' => 'Create cross-platform mobile applications for iOS and Android.',
                'category' => 'Technology'
            ],
            [
                'title' => 'Machine Learning Intern',
                'organization' => 'AI Innovations Lab',
                'required_skills' => ['Python', 'TensorFlow', 'Scikit-learn', 'Pandas'],
                'duration' => '6 months',
                'location' => 'Delhi',
                'work_type' => 'Remote',
                'description' => 'Develop machine learning models for business applications.',
                'category' => 'Technology'
            ],
            [
                'title' => 'Data Scientist Intern',
                'organization' => 'Analytics Pro',
                'required_skills' => ['Python', 'R', 'SQL', 'Tableau'],
                'duration' => '6 months',
                'location' => 'Mumbai',
                'work_type' => 'Remote',
                'description' => 'Analyze large datasets and create predictive models.',
                'category' => 'Technology'
            ],
        ];

        // Generate additional internships programmatically
        $companies = [
            'Infosys', 'Wipro', 'TCS', 'HCL Technologies', 'Tech Mahindra',
            'Capgemini', 'IBM India', 'Oracle India', 'SAP India', 'Salesforce',
            'Amazon India', 'Flipkart', 'Paytm', 'PhonePe', 'Razorpay'
        ];

        $skillSets = [
            ['Python', 'Django', 'PostgreSQL', 'Redis'],
            ['Java', 'Spring Boot', 'MySQL', 'Hibernate'],
            ['JavaScript', 'Vue.js', 'Node.js', 'MongoDB'],
            ['React', 'TypeScript', 'GraphQL', 'AWS'],
            ['Flutter', 'Dart', 'Firebase', 'SQLite'],
            ['Swift', 'iOS', 'Core Data', 'UIKit'],
            ['Kotlin', 'Android', 'Room Database', 'Retrofit'],
            ['HTML', 'CSS', 'JavaScript', 'Bootstrap'],
            ['C++', 'Data Structures', 'Algorithms', 'Problem Solving'],
            ['PHP', 'Laravel', 'MySQL', 'Redis']
        ];

        $jobTitles = [
            'Software Engineer', 'Backend Developer', 'Frontend Developer', 'Full Stack Developer',
            'Data Analyst', 'Business Analyst', 'Product Manager', 'QA Engineer',
            'DevOps Engineer', 'UI Designer', 'UX Designer', 'Content Writer',
            'Marketing Specialist', 'Sales Associate', 'HR Coordinator', 'Finance Analyst'
        ];

        $durations = ['3 months', '4 months', '5 months', '6 months'];
        $categories = ['Technology', 'Business', 'Marketing', 'Design'];

        for ($i = 0; $i < 50; $i++) {
            $company = $companies[array_rand($companies)];
            $title = $jobTitles[array_rand($jobTitles)] . ' Intern';
            $skills = $skillSets[array_rand($skillSets)];
            $location = $locations[array_rand($locations)];
            $workType = $workTypes[array_rand($workTypes)];
            $duration = $durations[array_rand($durations)];
            $category = $categories[array_rand($categories)];

            $internships[] = [
                'title' => $title,
                'organization' => $company,
                'required_skills' => $skills,
                'duration' => $duration,
                'location' => $location,
                'work_type' => $workType,
                'description' => "Join {$company} as a {$title} in {$location}. Gain valuable industry experience.",
                'category' => $category
            ];
        }

        // Insert into database
        foreach ($internships as $internship) {
            Internship::create($internship);
        }

        $this->command->info('Successfully seeded ' . count($internships) . ' internships!');
    }
}

-- Add sample data for a distinguished doctor
INSERT INTO doctors (
    doctor_name,
    specialization,
    qualification,
    experience_years,
    phone,
    email,
    bio,
    consultation_fee,
    status,
    profile_image
) VALUES (
    'Dr. Nguyen Van Minh',
    'Cardiology',
    'MD, Ph.D in Cardiology, Harvard Medical School',
    15,
    '+84 123 456 789',
    'dr.minh.cardio@hospital.com',
    'Dr. Nguyen Van Minh is a highly experienced cardiologist with over 15 years of practice. He specializes in interventional cardiology and has performed more than 1,000 successful cardiac procedures. He completed his medical degree and Ph.D. at Harvard Medical School and has been serving as the Head of Cardiology Department.',
    500000,
    'active',
    'default-doctor.jpg'
);

-- Get the last inserted doctor ID
SET @doctor_id = LAST_INSERT_ID();

-- Add doctor's schedule
INSERT INTO doctor_schedules (
    doctor_id,
    day_of_week,
    start_time,
    end_time,
    status
) VALUES
(@doctor_id, 'Monday', '08:00:00', '17:00:00', 'active'),
(@doctor_id, 'Wednesday', '08:00:00', '17:00:00', 'active'),
(@doctor_id, 'Friday', '08:00:00', '17:00:00', 'active');

-- Add doctor's specialization details
INSERT INTO doctor_specializations (
    doctor_id,
    specialization_name,
    description
) VALUES (
    @doctor_id,
    'Interventional Cardiology',
    'Specialized in cardiac catheterization, angioplasty, and stent placement procedures'
);
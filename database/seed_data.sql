USE jhub_africa_tracker;

INSERT INTO admins (username, password, name)
VALUES ('admin', '$2y$12$E3qSTthEaPvqFsoxsVSXguysD.h6aRBB9.LyNwJ61b98JfsDGjnbq', 'System Administrator');

INSERT INTO mentors (name, email, password, bio, expertise, created_by, years_experience)
VALUES
('John Kamau', 'john.kamau@jhubafrica.com', '$2y$12$wGlY1y25AIbwQplq3VBjpOewwZa9JkT/yqlG1wN/ZKfBWSiencx2q', 'Impact-focused mentor with a passion for clean energy.', 'Clean Energy', 1, 8),
('Grace Wanjiku', 'grace.wanjiku@jhubafrica.com', '$2y$12$wGlY1y25AIbwQplq3VBjpOewwZa9JkT/yqlG1wN/ZKfBWSiencx2q', 'Education reform advocate building inclusive learning networks.', 'EdTech', 1, 6);

INSERT INTO projects (name, description, date, email, website, profile_name, password, current_stage, current_percentage)
VALUES
('Solar Water Purifier', 'Developing a solar-powered purification system for rural communities.', CURDATE(), 'solar@jhubprojects.com', 'https://solarwater.example', 'solar_water', '$2y$12$UEP.OL..1RmlgJtEdZJ5Iu2/yoSpwHyTe.oX7vrxIlBwzHsC/kmHe', 2, 35),
('AgriSense IoT', 'IoT sensors delivering predictive insights for smallholder farmers.', CURDATE(), 'agri@jhubprojects.com', 'https://agrisense.example', 'agrisense_iot', '$2y$12$UEP.OL..1RmlgJtEdZJ5Iu2/yoSpwHyTe.oX7vrxIlBwzHsC/kmHe', 1, 15);

INSERT INTO project_innovators (project_id, name, email, role, experience_level)
VALUES
(1, 'Lucy Achieng', 'lucy@jhubprojects.com', 'Product Lead', 'Intermediate'),
(1, 'Peter Odhiambo', 'peter@jhubprojects.com', 'Engineer', 'Senior'),
(2, 'Mary Njeri', 'mary@jhubprojects.com', 'Founder', 'Intermediate');

INSERT INTO project_mentors (project_id, mentor_id, self_assigned)
VALUES
(1, 1, 0),
(2, 2, 1);

INSERT INTO ratings (project_id, mentor_id, stage, percentage, previous_stage, previous_percentage, notes)
VALUES
(1, 1, 2, 35, 1, 10, 'Completed onboarding and initial prototypes ready.'),
(2, 2, 1, 15, NULL, NULL, 'Early discovery interviews underway.');

INSERT INTO comments (project_id, user_type, user_id, commenter_name, comment_text)
VALUES
(1, 'mentor', 1, 'John Kamau', 'Great progress on the purification cartridges!'),
(1, 'project', 1, 'Solar Water Team', 'Thank you! Field tests scheduled next week.'),
(2, 'admin', 1, 'Administrator', 'Remember to document data privacy considerations.');

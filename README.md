# QuizVendor - AI-Powered Quiz Generation Platform

## Overview

QuizVendor is a web application designed to empower users, particularly educators and learners, to quickly and easily generate interactive quizzes on any topic using the power of Artificial Intelligence (specifically OpenAI's GPT models). Users can sign up, log in, create quizzes with various question types (multiple-choice, short answer, true/false), take quizzes, and view their history of attempts and scores.

The platform aims to make learning more engaging and assessment more efficient.

## Features

* **User Authentication:**
    * User Signup with email, name, and password.
    * User Login.
    * Session management to keep users logged in.
    * Logout functionality.
* **AI-Powered Quiz Generation:**
    * Users can specify a topic, dominant question type, difficulty, and number of questions.
    * Integrates with OpenAI API (GPT-3.5 Turbo) to generate quiz content.
    * Supports multiple-choice, short answer, and true/false questions.
* **Quiz Taking Interface:**
    * Dynamically displays generated quizzes for users to take.
    * Collects user answers.
* **Automated Grading:**
    * Compares user answers against AI-generated correct answers.
    * Calculates and displays the score as a percentage and correct/total count.
    * Shows a detailed breakdown of which questions were answered correctly or incorrectly, along with the correct answers for missed questions.
* **Quiz History:**
    * Logged-in users can view a history of quizzes they have taken, including the quiz title, date, and their score.
* **Responsive Design:**
    * User interface designed to adapt to various screen sizes (desktop, tablet, mobile).
* **Dynamic Content Loading:**
    * Uses JavaScript `fetch` API for asynchronous communication with backend PHP scripts, providing a smoother user experience without full page reloads for many actions.

## Tech Stack

* **Frontend:**
    * HTML5
    * CSS3 (with custom styling and responsive design principles)
    * JavaScript (ES6+) for DOM manipulation, event handling, and API calls (`fetch`)
* **Backend:**
    * PHP (v8.2.12 or similar)
    * MySQL (via XAMPP) for database storage
* **Database:**
    * MySQL
* **APIs:**
    * OpenAI API (GPT-3.5 Turbo for quiz content generation)
* **Development Environment:**
    * XAMPP (Apache, MySQL, PHP)

## File Structure (Simplified Overview)
```
Project_QuizVendor/
├── ASSETS/                     # Images, icons, etc.
│   ├── QuizVendor..png
│   └── cropped_image.png
│   └── founder_placeholder_X.png
├── php/                        # Backend PHP scripts
│   ├── db.php                  # Database connection
│   ├── signup.php              # User registration
│   ├── login.php               # User login
│   ├── logout.php              # User logout
│   ├── check_session.php       # Checks if user is logged in
│   ├── create_quiz.php         # Handles AI quiz generation & DB storage
│   ├── submit_quiz.php         # Handles quiz submission & grading
│   └── get_history.php         # Fetches user's quiz history
├── index.html                  # Home page (About, Founders)
├── getstarted.html             # Login/Signup page
├── dashboard.html              # User dashboard after login
├── create_quiz_page.html       # Page for creating and taking quizzes (or similar name)
├── history.html                # Page to display quiz history
├── takeQuiz.html               # (If you have a separate page for this)
├── pricing.html                # (Placeholder or future page)
└── feedback.html               # (Placeholder or future page)
└── about.html                  # About Us page
```
## Setup and Installation (XAMPP Environment)

1.  **Prerequisites:**
    * XAMPP installed (includes Apache, MySQL, PHP). Download from [Apache Friends](https://www.apachefriends.org).
    * Web browser (Chrome, Firefox, Edge, etc.).
    * Text editor or IDE (VS Code, Sublime Text, PhpStorm, etc.).
    * OpenAI API Key.

2.  **Clone or Download the Project:**
    * Place the entire `Project_QuizVendor` folder into your XAMPP `htdocs` directory (usually `C:\xampp\htdocs\Project_QuizVendor` or `/opt/lampp/htdocs/Project_QuizVendor`).

3.  **Start XAMPP:**
    * Open the XAMPP Control Panel.
    * Start the **Apache** and **MySQL** services.

4.  **Database Setup:**
    * Open phpMyAdmin in your browser (usually `http://localhost/phpmyadmin`).
    * Create a new database named `quizz_app` (or ensure the name matches what's in `php/db.php`).
    * Select the `quizz_app` database.
    * Go to the "SQL" tab and execute the full SQL script provided previously to create all necessary tables (`users`, `quizzes`, `questions`, `choices`, `quiz_attempts`, `user_answers`).

5.  **Configure PHP Scripts:**
    * **`php/db.php`**:
        * Verify the `$dbname` variable matches your database name (e.g., `quizz_app`).
        * Ensure `$user` and `$password` are correct for your MySQL setup (default for XAMPP root is usually user 'root' with no password).
    * **`php/create_quiz.php`**:
        * Replace `'YOUR_OPENAI_API_KEY_HERE'` with your actual OpenAI API key.
        * **Security Note:** For production, store API keys securely (e.g., environment variables or a config file outside the web root) instead of hardcoding. The key in the provided scripts has been exposed and should be revoked if it was a real key.

6.  **Access the Application:**
    * Open your web browser and navigate to the project, e.g., `http://localhost/Project_QuizVendor/index.html` or `http://localhost/Project_QuizVendor/getstarted.html`.

## Usage

1.  **Signup/Login:**
    * Navigate to `getstarted.html` (or `index.html` if it's your main entry with login/signup).
    * New users can sign up with their full name, email, and password.
    * Existing users can log in.
2.  **Dashboard:**
    * After logging in, users are typically redirected to `dashboard.html`.
    * The dashboard welcomes the user and provides navigation to other features.
3.  **Create Quiz:**
    * Navigate to the quiz creation page (e.g., linked from the dashboard or `create_quiz_page.html`).
    * Fill in the quiz topic, description (optional), dominant question type, difficulty, and number of questions.
    * Click "Generate Quiz." The system will use OpenAI to generate questions and save the quiz.
4.  **Take Quiz:**
    * After a quiz is generated, it will be displayed for the user to take.
    * Users can also navigate to a "Take Quiz" section (e.g., `takeQuiz.html` or a selection page) to find available quizzes (this feature might be a future enhancement if not already implemented).
    * Answer the questions and click "Submit Answers."
5.  **View Results:**
    * After submission, the score (percentage, correct/total) and a detailed breakdown of answers will be shown.
    * A progress bar visually represents the score.
6.  **Quiz History:**
    * Navigate to `history.html` (usually from the dashboard or main navigation).
    * View a table of all quizzes taken, including the title, date, score, and correct/total answers.
7.  **Logout:**
    * Click the "Logout" button/link to end the session.

## Key PHP Scripts and Roles

* **`db.php`**: Establishes the connection to the MySQL database.
* **`signup.php`**: Handles new user registration, including password hashing and duplicate email/username checks.
* **`login.php`**: Authenticates users against the database and establishes a session.
* **`logout.php`**: Destroys the user's session.
* **`check_session.php`**: Verifies if a user is currently logged in; used by frontend JavaScript to protect pages or customize UI.
* **`create_quiz.php`**:
    * Takes user input for quiz parameters.
    * Calls the OpenAI API to generate quiz questions and answers.
    * Parses the AI response.
    * Saves the quiz, questions, and choices (including correct answer information) to the database.
    * Returns the quiz structure (without answers) to the frontend for display.
* **`submit_quiz.php`**:
    * Receives user's answers for a quiz.
    * Creates a `quiz_attempts` record.
    * Saves individual answers to `user_answers`.
    * Retrieves correct answers from the database.
    * Grades the submission and calculates the score.
    * Updates the `quiz_attempts` record with the score.
    * Returns detailed results to the frontend.
* **`get_history.php`**:
    * Fetches the quiz attempt history for the logged-in user from the database.
    * Returns the history data as JSON.

## Database Schema Overview

The database `quizz_app` contains the following key tables:

* **`users`**: Stores user credentials and information (`user_id`, `username`, `email`, `password_hash`).
* **`quizzes`**: Stores general information about each quiz (`quiz_id`, `creator_user_id`, `title`, `description`).
* **`questions`**: Stores each question within a quiz (`question_id`, `quiz_id`, `question_text`, `question_type`, `correct_short_answer`).
* **`choices`**: Stores the options for multiple-choice and true/false questions (`choice_id`, `question_id`, `choice_text`, `is_correct`).
* **`quiz_attempts`**: Records each time a user attempts a quiz (`attempt_id`, `quiz_id`, `taker_user_id`, `score`, `attempt_datetime`).
* **`user_answers`**: Stores the specific answers given by a user for each question in an attempt (`user_answer_id`, `attempt_id`, `question_id`, `selected_choice_id`, `short_answer_text`, `is_correct`).

All tables use `ENGINE=InnoDB` to support foreign key constraints. Primary keys are generally `INT UNSIGNED AUTO_INCREMENT`.

## Future Enhancements / To-Do

* **Advanced User Roles:** Differentiate between regular users and admin/educator roles with different permissions.
* **Quiz Management for Creators:** Allow creators to view, edit, and delete quizzes they've made.
* **Public/Private Quizzes:** Option for creators to make quizzes public or private.
* **Quiz Sharing:** Allow users to share quizzes with others.
* **More Question Types:** Expand beyond MCQs, short answers, and true/false.
* **Image/Media in Questions:** Allow embedding images or other media in questions/choices.
* **Timed Quizzes:** Add a timer functionality.
* **Detailed Analytics:** Provide more in-depth analytics for quiz creators and takers.
* **Password Reset Functionality:** Implement a "Forgot Password" feature.
* **Enhanced UI/UX:** Continuously improve the user interface and experience.
* **API Security:** Implement more robust API security measures (e.g., CSRF tokens, rate limiting).
* **Refactor User ID Handling:** Consistently use session-derived user IDs in backend scripts instead of relying on client-side `localStorage` for critical operations.

## Contributing

Currently, this is a team project. If you wish to contribute in the future, please follow standard fork and pull request procedures. For major changes, please open an issue first to talk about what you'd like to change.

## License
MIT LicenseCopyright (c) 2025 [Mushaf Khalil]Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to dealing the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions: The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software. THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

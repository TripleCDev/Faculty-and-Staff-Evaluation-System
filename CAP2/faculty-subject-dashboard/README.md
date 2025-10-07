# Faculty & Subject Management Dashboard

## Overview
The Faculty & Subject Management Dashboard is a web application designed to manage faculty members and subjects within an educational institution. It provides a user-friendly interface for adding, editing, and deleting faculty and subject records, as well as assigning subjects to faculty members.

## Features
- **Faculty Management**: 
  - List all faculty members with search and filter options.
  - Add new faculty members through a modal form.
  - Edit existing faculty details.
  - Delete faculty members with confirmation.

- **Subject Management**: 
  - List all subjects with search and filter options.
  - Add new subjects through a modal form.
  - Edit existing subject details.
  - Delete subjects with confirmation.

- **Assignment Management**: 
  - Assign subjects to faculty members.
  - View and manage assignments.

## Technologies Used
- PHP: Server-side scripting language for backend development.
- MySQL: Database management system for storing faculty and subject records.
- TailwindCSS: Utility-first CSS framework for styling the dashboard.

## Installation
1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd faculty-subject-dashboard
   ```
3. Import the `database.sql` file into your MySQL database to set up the necessary tables.
4. Update the database connection settings in `src/db.php` as needed.
5. Start a local server (e.g., using XAMPP or MAMP) and navigate to `src/index.php` in your web browser.

## Usage
- Access the dashboard through the main entry point at `src/index.php`.
- Use the navigation to manage faculty and subjects.
- Follow the prompts to add, edit, or delete records as needed.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.
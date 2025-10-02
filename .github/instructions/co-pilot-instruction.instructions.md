---
applyTo: '**'
---
Provide project context and coding guidelines that AI should follow when generating code, answering questions, or reviewing changes.

# Copilot Instructions

When working in this codebase, please follow these guidelines:

### Project Context
- This is a Laravel-based e-commerce project that syncs with an ERP system
- database is MySQL
- The project handles products, customers, memberships, vouchers, loyalty points, promotions and related data
- Primary entry point for API interactions is `api.php`
- Uses PHP 8.4, Node.js 22.0, and Redis
- Testing framework: PestPHP for writing test cases
- Code quality tools:
  - Rector for automated refactoring
  - PHPStan for static analysis
  - Pint for code style enforcement
- Frontend:
  - Vite for asset compilation
  - Blade file used for render the view and some of file has Vue.js (Options API) components

### Code Structure and Organization
- Follow Domain Driven Design principles
- Place module-specific code in appropriate domain folders under `app/Domains/`
- Break code into multiple methods/classes for better maintainability
- Avoid large methods and excessive if-else conditions
- Prefer Laravel collections over array functions
- Use Eloquent ORM for all database interactions (avoid direct DB queries)
- For in page JS we are using dedicated files in `resources/js/pages/file_name`

### Naming Conventions
Follow these strict naming patterns:
- Files/Directories: `pos_erp`
- CSS Files: `pos_erp.css`
- JS Files: `pos_erp.js`
- Image Files: `pos-erp.png`
- Laravel View Files: `pos_erp.blade.php`
- HTML Class Names: `pos-erp`
- Input Names: `pos_erp`
- Hidden Input Names: `_pos_erp`
- Element IDs: `pos-erp`
- Class Names: `PosErp`
- Function Names: `posErp`
- Variable Names: `posErp`
- Enum Cases: `POS_ERP`
- Route Names: `pos_erp`
- Route URLs: `pos-erp`
- Database & Column Names: `pos_erp`
- Custom HTML css/id Prefix: `custom-`

### Code Style
- Use 4 spaces for indentation
- Always add a new empty line at the end of files
- Follow PSR standards for PHP code
- Maintain proper code documentation
- Explicitly specify columns in database queries
- Use database transactions for multiple write operations
- Avoid abort() inside DB transactions

### High-Risk Areas
Take extra care when working with:
- Product merging operations (product_id and related relations)
- Customer merging operations
- Database schema changes (always use new migrations)

### Performance and Security
- Write efficient, performant code
- Avoid unnecessary computations or database calls
- Use environment variables for sensitive information
- Implement proper error handling and logging
- Keep dependencies updated for security patches
- Use Laravel's built-in security features (CSRF, XSS protection)

### Testing Requirements
- Write automated tests using PestPHP
- Follow PestPHP's testing conventions and best practices
- Perform manual testing before creating pull requests
- Test UI/UX for user-friendliness
- Use transactions in tests to maintain database integrity
- Ensure proper error cases are tested
- Write both feature and unit tests as appropriate

### Code Quality Standards
- Run Rector before committing to ensure code follows modern PHP practices
- Maintain PHPStan level standards for static analysis
- Use Laravel Pint for consistent code style formatting
- Fix all static analysis warnings before submitting PRs
- Keep code quality tool configurations up to date

### Frontend Development
- Use Vite for asset compilation and optimization
- Follow Vue.js Options API patterns for component development
- Maintain consistent component structure across Vue files
- Use proper Vue.js lifecycle hooks and component organization
- Optimize asset compilation for production builds

### Database Practices
- Create new migrations for schema changes
- Prefix temporary migrations/jobs with "Temporary"
- Follow column alignment guidelines:
  - Right-align numeric columns
  - Left-align text columns
  - Center-align action columns

### Response Formats
When suggesting code changes:
1. Use proper error handling and validation
2. Follow RESTful conventions for APIs
3. Return consistent response structures
4. Include appropriate HTTP status codes
5. Properly handle and log errors

### Libraries and Dependencies
- Document new package additions in package.json/composer.json
- Include purpose comments for added packages
- Inform about additional software/PHP extension requirements

These instructions should guide the generation of code that aligns with the project's standards and best practices.

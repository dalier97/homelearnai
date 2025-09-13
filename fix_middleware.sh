#!/bin/bash

# Files to fix
files=(
    "tests/Feature/KidsModePinUITest.php"
    "tests/Feature/FlashcardImportFeatureTest.php"
    "tests/Feature/OnboardingFullWorkflowTest.php"
    "tests/Feature/OnboardingChildrenTest.php"
    "tests/Feature/FlashcardPrintControllerTest.php"
    "tests/Feature/KidsModeEnterExitTest.php"
    "tests/Feature/KidsModeControllerTest.php"
    "tests/Feature/KidsModeSecurityTest.php"
    "tests/Feature/KidsModePinUISimpleTest.php"
    "tests/Feature/KidsModeMiddlewareTest.php"
    "tests/Feature/KidsModeIntegrationTest.php"
    "tests/Feature/SubjectControllerRelationshipsTest.php"
    "tests/Feature/FlashcardCardTypesTest.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "Fixing $file"
        # Replace the withoutMiddleware() call
        sed -i 's/\$this->withoutMiddleware();/\/\/ Enable session middleware but disable unnecessary middleware for testing\n        \$this->withoutMiddleware([\n            \\App\\Http\\Middleware\\VerifyCsrfToken::class,\n        ]);/g' "$file"
    fi
done

echo "Done fixing middleware in test files"
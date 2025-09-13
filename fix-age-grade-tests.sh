#!/bin/bash

# Bulk fix script for age->grade field mismatches in E2E tests
echo "🔧 Fixing age->grade field mismatches in E2E tests..."

# Fix common age field references to grade field with age-to-grade mapping
files=(
  "tests/e2e/debug-selector-fixes.spec.ts"
  "tests/e2e/kids-mode-basic.spec.ts" 
  "tests/e2e/review-basic.spec.ts"
  "tests/e2e/review-system.spec.ts"
  "tests/e2e/test-isolation-verification.spec.ts"
  "tests/e2e/debug-child-id.spec.ts"
  "tests/e2e/debug-child-creation.spec.ts"
  "tests/e2e/flashcard-button-debug.spec.ts"
  "tests/e2e/homeschool-planning.spec.ts"
  "tests/e2e/flashcard-review-system.spec.ts"
  "tests/e2e/flashcard-unit-integration.spec.ts"
  "tests/e2e/flashcard-system-complete.spec.ts"
  "tests/e2e/homeschool-planning-simple.spec.ts"
  "tests/e2e/flashcard-import-system.spec.ts"
  "tests/e2e/quick-start-subjects.spec.ts"
  "tests/e2e/kids-mode.spec.ts"
)

# Age to grade mappings for common ages used in tests
declare -A age_to_grade=(
  ["5"]="K"
  ["6"]="1st"
  ["7"]="2nd"
  ["8"]="3rd"
  ["9"]="4th"
  ["10"]="5th"
  ["11"]="6th"
  ["12"]="7th"
  ["13"]="8th"
  ["14"]="9th"
  ["15"]="10th"
  ["16"]="11th"
  ["17"]="12th"
  ["18"]="12th"
)

for file in "${files[@]}"; do
  if [[ -f "$file" ]]; then
    echo "Processing: $file"
    
    # Replace select[name="age"] with select[name="grade"]
    sed -i 's/select\[name="age"\]/select[name="grade"]/g' "$file"
    
    # Replace common age values with grade values
    for age in "${!age_to_grade[@]}"; do
      grade="${age_to_grade[$age]}"
      # Replace age values in selectOption calls
      sed -i "s/selectOption([^,]*, ['\"]$age['\"])/selectOption(\1, '$grade')/g" "$file"
      # Replace age values in simple quotes
      sed -i "s/', '$age'/', '$grade'/g" "$file"
      sed -i 's/", "'$age'"/, "'$grade'"/g' "$file"
    done
    
    echo "✅ Fixed: $file"
  else
    echo "⚠️  File not found: $file"
  fi
done

echo "🎉 Bulk age->grade fixes completed!"

# Also fix onboarding testid issues in remaining files
onboarding_files=(
  "tests/e2e/onboarding-children-form.spec.ts"
  "tests/e2e/onboarding-edge-cases.spec.ts"
  "tests/e2e/onboarding-full-integration.spec.ts"
  "tests/e2e/onboarding-review-completion.spec.ts"
  "tests/e2e/onboarding-subjects-form.spec.ts"
)

echo "🔧 Fixing onboarding testid mismatches..."
for file in "${onboarding_files[@]}"; do
  if [[ -f "$file" ]]; then
    echo "Processing: $file"
    # Replace child-age-0 with child-grade-0 and update values
    sed -i 's/child-age-0/child-grade-0/g' "$file"
    sed -i 's/child-age-1/child-grade-1/g' "$file"
    sed -i 's/child-age-2/child-grade-2/g' "$file"
    
    # Replace common age values with grade values in onboarding
    for age in "${!age_to_grade[@]}"; do
      grade="${age_to_grade[$age]}"
      sed -i "s/selectOption([^,]*, ['\"]$age['\"])/selectOption(\1, '$grade')/g" "$file"
    done
    
    echo "✅ Fixed: $file"
  else
    echo "⚠️  File not found: $file"
  fi
done

echo "🎉 Onboarding testid fixes completed!"
echo "🚀 All bulk fixes applied. Run tests to verify improvements."
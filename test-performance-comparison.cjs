#!/usr/bin/env node

/**
 * Test Performance Comparison Script
 * 
 * Runs a subset of E2E tests to measure performance improvements
 * between optimized and non-optimized approaches.
 */

const { spawn } = require('child_process');
const fs = require('fs');

class TestPerformanceRunner {
  constructor() {
    this.results = {
      optimized: {
        tests: 0,
        passed: 0,
        failed: 0,
        totalTime: 0,
        averageTime: 0
      },
      baseline: {
        tests: 0,
        passed: 0,
        failed: 0,
        totalTime: 0,
        averageTime: 0
      }
    };
  }

  async runOptimizedTests() {
    console.log('🚀 Running optimized test suite...\n');
    
    const startTime = Date.now();
    
    // Run our optimized test file
    const testFiles = [
      'tests/e2e/optimized-homeschool-planning.spec.ts'
    ];
    
    try {
      const result = await this.runPlaywrightTests(testFiles);
      const endTime = Date.now();
      
      this.results.optimized.totalTime = endTime - startTime;
      this.results.optimized.tests = result.tests || 0;
      this.results.optimized.passed = result.passed || 0;
      this.results.optimized.failed = result.failed || 0;
      this.results.optimized.averageTime = this.results.optimized.tests > 0 
        ? this.results.optimized.totalTime / this.results.optimized.tests 
        : 0;
      
      console.log(`✅ Optimized tests completed in ${this.results.optimized.totalTime}ms`);
      console.log(`   Tests: ${this.results.optimized.tests}, Passed: ${this.results.optimized.passed}, Failed: ${this.results.optimized.failed}`);
      console.log(`   Average per test: ${Math.round(this.results.optimized.averageTime)}ms\n`);
      
    } catch (error) {
      console.error('❌ Optimized tests failed:', error.message);
      this.results.optimized.totalTime = Date.now() - startTime;
    }
  }

  async runBaselineTests() {
    console.log('📊 Running baseline test samples for comparison...\n');
    
    const startTime = Date.now();
    
    // Run a few existing tests as baseline
    const testFiles = [
      'tests/e2e/auth.spec.ts',
      'tests/e2e/curriculum-management.spec.ts'
    ];
    
    try {
      const result = await this.runPlaywrightTests(testFiles, 5000); // Limit to 5 tests max
      const endTime = Date.now();
      
      this.results.baseline.totalTime = endTime - startTime;
      this.results.baseline.tests = result.tests || 0;
      this.results.baseline.passed = result.passed || 0;
      this.results.baseline.failed = result.failed || 0;
      this.results.baseline.averageTime = this.results.baseline.tests > 0 
        ? this.results.baseline.totalTime / this.results.baseline.tests 
        : 0;
      
      console.log(`📈 Baseline tests completed in ${this.results.baseline.totalTime}ms`);
      console.log(`   Tests: ${this.results.baseline.tests}, Passed: ${this.results.baseline.passed}, Failed: ${this.results.baseline.failed}`);
      console.log(`   Average per test: ${Math.round(this.results.baseline.averageTime)}ms\n`);
      
    } catch (error) {
      console.error('❌ Baseline tests failed:', error.message);
      this.results.baseline.totalTime = Date.now() - startTime;
    }
  }

  async runPlaywrightTests(testFiles, maxTests = 999) {
    return new Promise((resolve, reject) => {
      const args = [
        'test',
        '--config', 'playwright.config.js',
        '--reporter=json',
        ...testFiles
      ];

      if (maxTests < 999) {
        // Add a grep pattern to limit tests if needed
        args.push('--max-failures', '5');
      }

      const child = spawn('npx', ['playwright', ...args], {
        stdio: ['pipe', 'pipe', 'pipe'],
        cwd: process.cwd()
      });

      let stdout = '';
      let stderr = '';

      child.stdout.on('data', (data) => {
        stdout += data.toString();
      });

      child.stderr.on('data', (data) => {
        stderr += data.toString();
        // Show real-time progress
        if (data.toString().includes('Running') || data.toString().includes('PASS') || data.toString().includes('FAIL')) {
          process.stdout.write('.');
        }
      });

      child.on('close', (code) => {
        console.log(); // New line after dots
        
        try {
          // Try to parse JSON output
          const jsonMatch = stdout.match(/\{[\s\S]*\}/);
          let result = { tests: 0, passed: 0, failed: 0 };
          
          if (jsonMatch) {
            const testResult = JSON.parse(jsonMatch[0]);
            result.tests = testResult.suites?.reduce((acc, suite) => 
              acc + (suite.specs?.length || 0), 0) || 0;
            result.passed = testResult.suites?.reduce((acc, suite) => 
              acc + (suite.specs?.filter(spec => spec.ok)?.length || 0), 0) || 0;
            result.failed = result.tests - result.passed;
          } else {
            // Fallback: parse from stderr output
            const testLines = stderr.split('\n').filter(line => 
              line.includes('✓') || line.includes('×') || line.includes('PASS') || line.includes('FAIL')
            );
            result.tests = testLines.length;
            result.passed = testLines.filter(line => 
              line.includes('✓') || line.includes('PASS')
            ).length;
            result.failed = result.tests - result.passed;
          }
          
          if (code === 0 || result.tests > 0) {
            resolve(result);
          } else {
            reject(new Error(`Tests failed with code ${code}. Output: ${stderr}`));
          }
        } catch (parseError) {
          // If we can't parse, at least return basic info
          resolve({ tests: 1, passed: code === 0 ? 1 : 0, failed: code === 0 ? 0 : 1 });
        }
      });

      child.on('error', (error) => {
        reject(error);
      });
    });
  }

  generateReport() {
    console.log('\n' + '='.repeat(60));
    console.log('📊 PERFORMANCE COMPARISON REPORT');
    console.log('='.repeat(60));
    
    console.log('\n🚀 OPTIMIZED TESTS:');
    console.log(`   Total Time: ${this.results.optimized.totalTime}ms`);
    console.log(`   Tests: ${this.results.optimized.tests} (${this.results.optimized.passed} passed, ${this.results.optimized.failed} failed)`);
    console.log(`   Average per test: ${Math.round(this.results.optimized.averageTime)}ms`);
    
    console.log('\n📈 BASELINE TESTS:');
    console.log(`   Total Time: ${this.results.baseline.totalTime}ms`);
    console.log(`   Tests: ${this.results.baseline.tests} (${this.results.baseline.passed} passed, ${this.results.baseline.failed} failed)`);
    console.log(`   Average per test: ${Math.round(this.results.baseline.averageTime)}ms`);
    
    if (this.results.baseline.averageTime > 0 && this.results.optimized.averageTime > 0) {
      const improvement = ((this.results.baseline.averageTime - this.results.optimized.averageTime) / this.results.baseline.averageTime) * 100;
      
      console.log('\n⚡ PERFORMANCE IMPROVEMENT:');
      if (improvement > 0) {
        console.log(`   ${improvement.toFixed(1)}% faster per test!`);
        console.log(`   Saved ${Math.round(this.results.baseline.averageTime - this.results.optimized.averageTime)}ms per test`);
        
        // Extrapolate to full suite
        const totalTests = 210; // Approximate total test count
        const totalSavings = (this.results.baseline.averageTime - this.results.optimized.averageTime) * totalTests;
        console.log(`   Projected total savings for ${totalTests} tests: ${Math.round(totalSavings / 1000)}s`);
        
        if (totalSavings > 60000) {
          console.log(`   That's ${Math.round(totalSavings / 60000)} minutes saved! 🎉`);
        }
      } else {
        console.log(`   Tests are ${Math.abs(improvement).toFixed(1)}% slower (needs more optimization)`);
      }
    }
    
    console.log('\n💡 RECOMMENDATIONS:');
    if (this.results.optimized.averageTime < 5000) {
      console.log('   ✅ Excellent performance! Tests under 5 seconds each.');
    } else if (this.results.optimized.averageTime < 10000) {
      console.log('   ⚠️  Good performance, but could be faster. Target: under 5 seconds.');
    } else {
      console.log('   ❌ Tests still too slow. More optimization needed.');
    }
    
    console.log('   📝 Next steps:');
    console.log('      - Apply optimizations to remaining test files');
    console.log('      - Use shared test data helpers throughout');
    console.log('      - Implement database transaction rollbacks');
    console.log('      - Run full optimized suite with: npm run test:e2e');
    
    console.log('\n' + '='.repeat(60));
  }

  async run() {
    console.log('🧪 Starting E2E Test Performance Comparison\n');
    
    // Run optimized tests first
    await this.runOptimizedTests();
    
    // Run baseline tests for comparison
    await this.runBaselineTests();
    
    // Generate comprehensive report
    this.generateReport();
  }
}

// Run the performance comparison
if (require.main === module) {
  const runner = new TestPerformanceRunner();
  runner.run().catch(error => {
    console.error('❌ Performance test failed:', error);
    process.exit(1);
  });
}

module.exports = TestPerformanceRunner;
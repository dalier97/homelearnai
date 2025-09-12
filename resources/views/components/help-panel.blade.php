{{-- Help Panel Component --}}
@props([
    'context' => 'general',
    'showVideos' => true,
    'showQuickTips' => true,
    'showFaq' => true
])

<div x-data="{ 
    open: false, 
    activeTab: 'quickstart',
    helpContext: '{{ $context }}',
    searchQuery: '',
    searchResults: []
}" 
    x-show="open" 
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    @keydown.escape="open = false">
    
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-500 bg-opacity-75" @click="open = false"></div>
    
    <!-- Panel -->
    <div class="absolute right-0 top-0 h-full w-full max-w-2xl bg-white shadow-xl transform transition-transform duration-300 ease-in-out"
         :class="open ? 'translate-x-0' : 'translate-x-full'"
         @click.stop>
        
        <!-- Header -->
        <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h2 class="text-xl font-semibold">Help Center</h2>
                        <p class="text-blue-100 text-sm" x-text="'Context: ' + helpContext.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></p>
                    </div>
                </div>
                <button @click="open = false" class="text-white hover:text-blue-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="mt-4">
                <div class="relative">
                    <input 
                        type="text"
                        x-model="searchQuery"
                        @input.debounce.300ms="searchHelp()"
                        placeholder="Search help articles, videos, and guides..."
                        class="w-full px-4 py-2 pl-10 text-gray-900 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300"
                    >
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Help sections">
                <button @click="activeTab = 'quickstart'" 
                        :class="activeTab === 'quickstart' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Quick Start
                </button>
                @if($showVideos)
                <button @click="activeTab = 'videos'" 
                        :class="activeTab === 'videos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Video Tutorials
                </button>
                @endif
                @if($showQuickTips)
                <button @click="activeTab = 'tips'" 
                        :class="activeTab === 'tips' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Quick Tips
                </button>
                @endif
                @if($showFaq)
                <button @click="activeTab = 'faq'" 
                        :class="activeTab === 'faq' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    FAQ
                </button>
                @endif
                <button @click="activeTab = 'contact'" 
                        :class="activeTab === 'contact' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Contact
                </button>
            </nav>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            
            <!-- Quick Start Tab -->
            <div x-show="activeTab === 'quickstart'" class="space-y-6">
                <div class="bg-blue-50 rounded-lg p-4" x-show="helpContext === 'flashcards'">
                    <h3 class="text-lg font-medium text-blue-900 mb-3">Getting Started with Flashcards</h3>
                    <div class="space-y-3 text-sm text-blue-800">
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                            <div>
                                <p class="font-medium">Create Your First Flashcard</p>
                                <p class="text-blue-700">Click "Add Flashcard" and start with a basic question and answer.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                            <div>
                                <p class="font-medium">Choose the Right Card Type</p>
                                <p class="text-blue-700">Basic for simple Q&A, Multiple Choice for recognition, Cloze for fill-in-the-blank.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                            <div>
                                <p class="font-medium">Test in Review System</p>
                                <p class="text-blue-700">Start a review session to see how your flashcards work with spaced repetition.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Context-specific quick guides -->
                <div x-show="helpContext === 'import'">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Import Quick Guide</h3>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="font-medium text-green-900 mb-2">From Quizlet</h4>
                        <ol class="list-decimal list-inside space-y-1 text-sm text-green-800">
                            <li>Export your Quizlet set as text</li>
                            <li>Copy and paste into our import box</li>
                            <li>Preview and adjust if needed</li>
                            <li>Click Import to add to your unit</li>
                        </ol>
                    </div>
                </div>

                <!-- Common Actions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Common Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="/docs/user/parent-guide.md" target="_blank" 
                           class="flex items-center p-3 bg-white rounded border hover:bg-gray-50">
                            <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Parent Guide</p>
                                <p class="text-sm text-gray-600">Complete documentation</p>
                            </div>
                        </a>
                        <a href="/docs/user/kids-guide.md" target="_blank"
                           class="flex items-center p-3 bg-white rounded border hover:bg-gray-50">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Kids Guide</p>
                                <p class="text-sm text-gray-600">Child-friendly instructions</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Video Tutorials Tab -->
            @if($showVideos)
            <div x-show="activeTab === 'videos'" class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Video Tutorials</h3>
                
                <div class="grid gap-4">
                    <!-- Video placeholder cards -->
                    <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-24 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Getting Started with Flashcards</h4>
                                <p class="text-sm text-gray-600 mt-1">Learn how to create your first flashcards and use different card types effectively.</p>
                                <p class="text-xs text-gray-500 mt-2">Duration: 5 minutes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-24 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Importing from Quizlet and Anki</h4>
                                <p class="text-sm text-gray-600 mt-1">Step-by-step guide to importing your existing flashcard collections.</p>
                                <p class="text-xs text-gray-500 mt-2">Duration: 6 minutes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-24 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Using the Review System</h4>
                                <p class="text-sm text-gray-600 mt-1">Master spaced repetition and make the most of the intelligent review system.</p>
                                <p class="text-xs text-gray-500 mt-2">Duration: 4 minutes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Quick Tips Tab -->
            @if($showQuickTips)
            <div x-show="activeTab === 'tips'" class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Tips & Best Practices</h3>
                
                <div class="space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="flex-shrink-0 w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-yellow-900">Pro Tip: Start Small</h4>
                                <p class="text-sm text-yellow-800 mt-1">Create 5-10 flashcards first to test your approach, then build your collection gradually.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="flex-shrink-0 w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-blue-900">Card Writing Best Practice</h4>
                                <p class="text-sm text-blue-800 mt-1">Keep questions specific and answers concise. Use hints liberally for kids mode.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="flex-shrink-0 w-5 h-5 text-green-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-green-900">Import Tip</h4>
                                <p class="text-sm text-green-800 mt-1">Always preview your imports! Check for formatting issues before finalizing.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- FAQ Tab -->
            @if($showFaq)
            <div x-show="activeTab === 'faq'" class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Frequently Asked Questions</h3>
                
                <div class="space-y-4" x-data="{ openFaq: null }">
                    <div class="border border-gray-200 rounded-lg">
                        <button @click="openFaq = openFaq === 1 ? null : 1" 
                                class="w-full text-left px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">How do I import cards from Quizlet?</span>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                     :class="openFaq === 1 ? 'rotate-180' : ''" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div x-show="openFaq === 1" x-collapse class="px-4 pb-3">
                            <p class="text-sm text-gray-600">Export your Quizlet set as text, then use our copy/paste import method. The system auto-detects the format and imports your cards seamlessly.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg">
                        <button @click="openFaq = openFaq === 2 ? null : 2" 
                                class="w-full text-left px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">Can kids create their own flashcards?</span>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                     :class="openFaq === 2 ? 'rotate-180' : ''" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div x-show="openFaq === 2" x-collapse class="px-4 pb-3">
                            <p class="text-sm text-gray-600">In Kids Mode, children can study flashcards but cannot create, edit, or delete them. This ensures content quality while allowing independent study.</p>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg">
                        <button @click="openFaq = openFaq === 3 ? null : 3" 
                                class="w-full text-left px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">How does the spaced repetition work?</span>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform" 
                                     :class="openFaq === 3 ? 'rotate-180' : ''" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        <div x-show="openFaq === 3" x-collapse class="px-4 pb-3">
                            <p class="text-sm text-gray-600">Cards you find easy appear less frequently, while difficult cards come up more often. The system adapts based on your performance ratings after each review.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="/docs/faq.md" target="_blank" 
                       class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <span>View complete FAQ</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
            </div>
            @endif
            
            <!-- Contact Tab -->
            <div x-show="activeTab === 'contact'" class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Get Help</h3>
                
                <div class="grid gap-4">
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="flex-shrink-0 w-6 h-6 text-blue-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-gray-900">Email Support</h4>
                                <p class="text-sm text-gray-600 mt-1">Get personalized help with any questions or issues.</p>
                                <a href="mailto:support@learningapp.com" class="text-blue-600 hover:text-blue-800 text-sm">support@learningapp.com</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="flex-shrink-0 w-6 h-6 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-6a2 2 0 012-2h8z"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-gray-900">Community Forum</h4>
                                <p class="text-sm text-gray-600 mt-1">Connect with other homeschool families and share tips.</p>
                                <a href="#" class="text-green-600 hover:text-green-800 text-sm">Join the discussion</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="flex-shrink-0 w-6 h-6 text-purple-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-gray-900">Documentation</h4>
                                <p class="text-sm text-gray-600 mt-1">Comprehensive guides and technical documentation.</p>
                                <a href="/docs" class="text-purple-600 hover:text-purple-800 text-sm">Browse documentation</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info for Support -->
                <div class="bg-gray-50 rounded-lg p-4 mt-6">
                    <h4 class="font-medium text-gray-900 mb-2">System Information</h4>
                    <div class="text-xs text-gray-600 space-y-1">
                        <div>Browser: <span x-text="navigator.userAgent.split('(')[0]"></span></div>
                        <div>Page: <span x-text="window.location.pathname"></span></div>
                        <div>Context: <span x-text="helpContext"></span></div>
                        <div>Timestamp: <span x-text="new Date().toISOString()"></span></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Include this information when contacting support for faster assistance.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Help Panel Toggle Button --}}
<button @click="$dispatch('toggle-help-panel')" 
        class="fixed bottom-4 right-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-3 shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 z-40 transition-colors duration-200"
        title="Open Help Center">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
</button>

@once
<script>
    // Initialize help panel functionality
    document.addEventListener('alpine:init', () => {
        Alpine.data('helpPanel', () => ({
            init() {
                // Listen for help panel toggle events
                this.$watch('open', (value) => {
                    if (value) {
                        document.body.style.overflow = 'hidden';
                        this.trackHelpUsage('panel_opened', this.helpContext);
                    } else {
                        document.body.style.overflow = 'auto';
                    }
                });
                
                // Global help panel toggle
                this.$dispatch('toggle-help-panel', () => {
                    this.open = !this.open;
                });
            },
            
            searchHelp() {
                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }
                
                // Simulate search functionality
                const mockResults = [
                    { title: 'Creating Your First Flashcard', type: 'guide', url: '/docs/user/parent-guide.md#creating-flashcards' },
                    { title: 'Import from Quizlet', type: 'tutorial', url: '/docs/guides/import-export.md#quizlet-import' },
                    { title: 'Card Types Explained', type: 'video', url: '#video-card-types' }
                ].filter(item => 
                    item.title.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
                
                this.searchResults = mockResults;
            },
            
            trackHelpUsage(action, context) {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'help_usage', {
                        'action': action,
                        'context': context,
                        'search_query': this.searchQuery || null
                    });
                }
            }
        }));
    });
    
    // Global help panel trigger
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for help panel toggle event
        document.addEventListener('toggle-help-panel', function() {
            // This will be handled by Alpine.js
        });
        
        // Keyboard shortcut for help
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'H') {
                e.preventDefault();
                document.dispatchEvent(new CustomEvent('toggle-help-panel'));
            }
        });
    });
</script>
@endonce
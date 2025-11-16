<div id="ddtg-timeline-game-wrapper" class="ddtg-timeline-wrapper">

    <!-- HEADER WITH DROP ZONES -->
    <header id="top-bar-drop-zone" class="h-[100px] flex items-center justify-center p-3 bg-white shadow-xl z-30">
        <div class="flex space-x-2 w-full max-w-6xl mx-auto items-center">
            <div id="drop-zone-items" class="flex items-center justify-center space-x-2 w-full">
                <!-- Dynamic placeholders inserted by JS -->
            </div>
            <button id="finish-btn"
                class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-200 shadow-lg flex-shrink-0 text-lg font-semibold whitespace-nowrap focus:outline-none focus:ring-4 focus:ring-green-300">
                Finish
            </button>
        </div>
    </header>

    <!-- MAIN CONTENT AREA -->
    <main id="main-content-area" class="flex-1 flex flex-col items-center justify-center p-8 relative">
        <h2 class="text-2xl font-bold text-gray-700 mb-6 hidden md:block">
            Order the events chronologically (Earliest in Slot 1)
        </h2>

        <div id="carousel"
            class="relative w-full max-w-4xl h-full flex items-center justify-center">
            <div id="slides-wrapper" class="relative w-full h-[80%] md:h-[90%]">
                <!-- Slides inserted by JS -->
            </div>

            <button id="prev-btn"
                class="absolute left-0 p-3 bg-white/70 backdrop-blur-sm rounded-full shadow-lg hover:bg-white transition duration-200 z-40 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed">
                ‹
            </button>
            <button id="next-btn"
                class="absolute right-0 p-3 bg-white/70 backdrop-blur-sm rounded-full shadow-lg hover:bg-white transition duration-200 z-40 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed">
                ›
            </button>
        </div>
    </main>

    <!-- MODAL -->
    <div id="message-modal"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-[100]">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full text-center">
            <h3 id="modal-title" class="text-xl font-bold text-green-600 mb-3">Result</h3>
            <p id="modal-message" class="text-gray-700 mb-4"></p>
            <button onclick="closeModal()"
                class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                Close
            </button>
        </div>
    </div>

</div>

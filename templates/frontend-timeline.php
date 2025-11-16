<div id="ddg-game-wrapper" class="ddg-wrapper mx-auto max-w-3xl p-4">

    <h2 class="text-center text-2xl font-bold mb-6">
        <?php echo esc_html( $game->game_name ); ?>
    </h2>

    <!-- Drop Zones -->
    <div id="drop-zone-items" class="grid grid-cols-1 gap-4 mb-10">
        <!-- JS will inject drop slots here -->
    </div>

    <!-- Timeline Carousel -->
    <div class="relative w-full h-96 overflow-hidden border rounded-xl shadow">
        <div id="slides-wrapper" class="absolute inset-0 flex transition-transform duration-300 ease-in-out">
            <!-- JS will inject slides here -->
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between items-center mt-4">
        <button id="prev-btn" class="px-4 py-2 bg-gray-300 rounded">Prev</button>
        <button id="next-btn" class="px-4 py-2 bg-gray-300 rounded">Next</button>
        <button id="finish-btn" class="px-4 py-2 bg-green-600 text-white rounded">Finish</button>
    </div>

</div>

<!-- Modal -->
<div id="message-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 max-w-md text-center shadow-xl">
        <h3 id="modal-title" class="text-xl font-bold mb-3"></h3>
        <p id="modal-message" class="mb-4"></p>
        <button onclick="window.closeModal()" class="px-4 py-2 bg-blue-600 text-white rounded">
            Close
        </button>
    </div>
</div>

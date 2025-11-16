// game_events will be injected by PHP using the shortcode renderer.

document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('ddg-game-wrapper');
    if (!wrapper) return;

    const events = (
        window.ddgGameData &&
        Array.isArray(window.ddgGameData.events)
    ) ? window.ddgGameData.events : [];

    const dropZoneContainer = document.getElementById('drop-zone-items');
    const slidesWrapper = document.getElementById('slides-wrapper');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const finishBtn = document.getElementById('finish-btn');
    const modal = document.getElementById('message-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');

    let currentIndex = 0;
    const placements = {};
    const slides = [];

    const parseDate = (value) => {
        if (value === null || value === undefined) return null;
        if (typeof value === 'number') return value;

        const parsed = Date.parse(String(value));
        if (!Number.isNaN(parsed)) return parsed;

        const numeric = Number(value);
        return Number.isNaN(numeric) ? null : numeric;
    };

    const closeModal = () => {
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    window.closeModal = closeModal;

    const showMessage = (title, htmlMessage, statusClass = '') => {
        if (modalTitle) {
            modalTitle.textContent = title;
            modalTitle.className = `text-xl font-bold mb-3 ${statusClass}`.trim();
        }

        if (modalMessage) {
            modalMessage.innerHTML = htmlMessage;
        }

        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    const buildDropZones = () => {
        if (!dropZoneContainer) return;

        dropZoneContainer.innerHTML = '';

        if (!events.length) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'text-gray-600';
            emptyMessage.textContent = 'No events available yet.';
            dropZoneContainer.appendChild(emptyMessage);
            if (finishBtn) finishBtn.disabled = true;
            return;
        }

        events.forEach((event, index) => {
            const slot = document.createElement('div');
            slot.className = 'flex items-center justify-center bg-gray-100 border border-dashed border-gray-400 rounded-lg min-h-[60px] px-3 py-2 text-center shadow-inner drop-slot placeholder';
            slot.dataset.slotIndex = String(index);
            slot.dataset.expectedDate = event.date ?? '';

            slot.addEventListener('dragover', (e) => {
                e.preventDefault();
                slot.classList.add('ring-2', 'ring-blue-400');
            });

            slot.addEventListener('dragleave', () => {
                slot.classList.remove('ring-2', 'ring-blue-400');
            });

            slot.addEventListener('drop', (e) => {
                e.preventDefault();
                slot.classList.remove('ring-2', 'ring-blue-400');
                const draggedId = e.dataTransfer?.getData('text/plain');
                if (!draggedId) return;

                const draggedSlide = document.getElementById(draggedId);
                if (!draggedSlide) return;

                const previousSlot = draggedSlide.parentElement;
                if (previousSlot && previousSlot.classList.contains('drop-slot')) {
                    delete placements[previousSlot.dataset.slotIndex || ''];
                }

                slot.innerHTML = '';
                slot.appendChild(draggedSlide);
                placements[slot.dataset.slotIndex || ''] = draggedId;
            });

            dropZoneContainer.appendChild(slot);
        });
    };

    const updateNavigationButtons = () => {
        if (prevBtn) {
            prevBtn.disabled = currentIndex === 0 || slides.length === 0;
        }
        if (nextBtn) {
            nextBtn.disabled = currentIndex >= slides.length - 1 || slides.length === 0;
        }
    };

    const showSlide = (index) => {
        if (!slides.length || !slidesWrapper) return;

        const boundedIndex = Math.max(0, Math.min(index, slides.length - 1));
        currentIndex = boundedIndex;
        const offset = -boundedIndex * 100;
        slidesWrapper.style.transform = `translateX(${offset}%)`;
        updateNavigationButtons();
    };

    const buildSlides = () => {
        if (!slidesWrapper) return;

        slidesWrapper.innerHTML = '';
        slides.length = 0;

        events.forEach((event, index) => {
            const slide = document.createElement('div');
            slide.id = `timeline-slide-${event.id ?? index}`;
            slide.className = 'carousel-slide absolute inset-0 flex flex-col items-center justify-center bg-white shadow-lg rounded-xl p-6 transition-transform duration-300 ease-in-out';
            slide.style.transform = `translateX(${index * 100}%)`;
            slide.setAttribute('draggable', 'true');
            slide.dataset.eventDate = event.date ?? '';
            slide.dataset.eventIndex = String(index);

            const title = document.createElement('h3');
            title.className = 'text-xl font-semibold text-gray-800 text-center mb-3';
            title.textContent = event.name || `Event ${index + 1}`;
            slide.appendChild(title);

            if (event.image) {
                const image = document.createElement('img');
                image.src = event.image;
                image.alt = event.name || '';
                image.className = 'max-h-48 w-auto rounded-lg shadow mb-3 object-contain';
                slide.appendChild(image);
            }

            if (event.description) {
                const description = document.createElement('p');
                description.className = 'text-gray-600 text-center mb-2';
                description.textContent = event.description;
                slide.appendChild(description);
            }

            const dateHint = document.createElement('p');
            dateHint.className = 'text-sm text-gray-500 italic';
            dateHint.textContent = event.date ?? '';
            slide.appendChild(dateHint);

            slide.addEventListener('dragstart', (e) => {
                e.dataTransfer?.setData('text/plain', slide.id);
                setTimeout(() => {
                    slide.classList.add('opacity-0');
                }, 0);
            });

            slide.addEventListener('dragend', () => {
                slide.classList.remove('opacity-0');
            });

            slides.push(slide);
            slidesWrapper.appendChild(slide);
        });

        updateNavigationButtons();
        showSlide(0);
    };

    const getSortedEventIds = () => {
        return events
            .map((event, index) => ({
                id: `timeline-slide-${event.id ?? index}`,
                date: parseDate(event.date ?? '') ?? Infinity,
            }))
            .sort((a, b) => a.date - b.date)
            .map((entry) => entry.id);
    };

    const lockUIAfterFinish = () => {
        document.querySelectorAll('.placeholder').forEach((p) => {
            p.classList.add('opacity-60');
            p.style.pointerEvents = 'none';
        });

        document.querySelectorAll('.carousel-slide').forEach((slide) => {
            slide.setAttribute('draggable', 'false');
            slide.style.pointerEvents = 'none';
        });
    };

    const checkOrder = () => {
        const sortedIds = getSortedEventIds();
        const slots = Object.keys(placements)
            .map((key) => ({ slot: Number(key), id: placements[key] }))
            .sort((a, b) => a.slot - b.slot);

        if (slots.length !== events.length) {
            showMessage('Incomplete', 'Place all events into the slots before finishing.', 'text-yellow-600');
            if (finishBtn) {
                finishBtn.disabled = false;
                finishBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return;
        }

        const correctCount = slots.reduce((count, slot, index) => count + (slot.id === sortedIds[index] ? 1 : 0), 0);
        const totalEvents = events.length;
        const isCorrect = correctCount === totalEvents;

        const resultTitle = isCorrect ? 'Great job!' : 'Almost there';
        const resultHTML = isCorrect
            ? 'You ordered all events correctly.'
            : `You got ${correctCount} of ${totalEvents} in the right spot.`;
        const resultClass = isCorrect ? 'text-green-600' : 'text-red-600';

        lockUIAfterFinish();
        showMessage(resultTitle, resultHTML, resultClass);
    };

    const onFinishClick = () => {
        if (!finishBtn) return;

        finishBtn.disabled = true;
        finishBtn.classList.add('opacity-50', 'cursor-not-allowed');
        checkOrder();
    };

    window.navigateSlide = (direction) => {
        showSlide(currentIndex + direction);
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', () => navigateSlide(-1));
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => navigateSlide(1));
    }

    if (finishBtn) {
        finishBtn.addEventListener('click', onFinishClick);
    }

    buildDropZones();
    buildSlides();
});

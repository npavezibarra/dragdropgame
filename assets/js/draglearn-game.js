document.addEventListener('DOMContentLoaded', function() {
    const game = document.getElementById('draglearn-game');

    if (!game) {
        return;
    }

    const eventsData = Array.isArray(window.dragdropgame_events) ? window.dragdropgame_events : [];
    const eventsContainer = document.getElementById('ddtg-events');
    const datesContainer = document.getElementById('ddtg-dates');
    const feedback = document.getElementById('feedback');
    const attemptId = game.dataset.attemptId;

    if (!eventsContainer || !datesContainer || eventsData.length === 0) {
        if (feedback) {
            feedback.textContent = 'No events available.';
            feedback.className = 'feedback incorrect';
        }
        return;
    }

    const shuffleArray = (array) => {
        const clone = array.slice();
        for (let i = clone.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [clone[i], clone[j]] = [clone[j], clone[i]];
        }
        return clone;
    };

    const dropZones = [];
    const draggables = [];
    const shuffledDates = shuffleArray(eventsData.map(event => event.event_date));

    eventsData.forEach((event) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'draggable';
        wrapper.setAttribute('draggable', 'true');
        wrapper.dataset.eventDate = event.event_date;

        const title = document.createElement('strong');
        title.textContent = event.event_name;
        wrapper.appendChild(title);

        if (event.description) {
            const description = document.createElement('p');
            description.className = 'event-description';
            description.textContent = event.description;
            wrapper.appendChild(description);
        }

        if (event.image_url) {
            const image = document.createElement('img');
            image.src = event.image_url;
            image.alt = event.event_name;
            wrapper.appendChild(image);
        }

        eventsContainer.appendChild(wrapper);
        draggables.push(wrapper);
    });

    shuffledDates.forEach((date) => {
        const zone = document.createElement('div');
        zone.className = 'drop-zone';
        zone.dataset.eventDate = date;

        const heading = document.createElement('h4');
        heading.textContent = date;
        zone.appendChild(heading);

        datesContainer.appendChild(zone);
        dropZones.push(zone);
    });

    let correctlyPlaced = 0;
    let draggedItem = null;

    const checkOrder = (draggableItem, dropZone) => {
        if (!draggableItem || !dropZone) {
            return false;
        }

        return draggableItem.dataset.eventDate === dropZone.dataset.eventDate;
    };

    const saveScore = () => {
        const score = correctlyPlaced;
        jQuery.ajax({
            url: ddtg_game_data.ajax_url,
            type: 'POST',
            data: {
                action: 'record_score',
                nonce: ddtg_game_data.nonce,
                attempt_id: attemptId,
                score: score
            },
            success: function(response) {
                if (response.success) {
                    feedback.textContent += ' Your score has been saved.';
                } else {
                    feedback.textContent += ' There was an error saving your score.';
                }
            }
        });
    };

    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', function() {
            draggedItem = this;
            setTimeout(() => { this.style.display = 'none'; }, 0);
        });

        draggable.addEventListener('dragend', function() {
            setTimeout(() => {
                draggedItem.style.display = 'block';
                draggedItem = null;
            }, 0);
        });
    });

    dropZones.forEach(zone => {
        zone.addEventListener('dragover', e => e.preventDefault());
        zone.addEventListener('dragenter', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#e0e0e0';
        });
        zone.addEventListener('dragleave', function() { this.style.backgroundColor = ''; });
        zone.addEventListener('drop', function() {
            this.style.backgroundColor = '';
            if (draggedItem) {
                const isCorrect = checkOrder(draggedItem, this);

                if (isCorrect) {
                    this.appendChild(draggedItem);
                    draggedItem.setAttribute('draggable', 'false');
                    correctlyPlaced++;
                    feedback.textContent = 'Correct!';
                    feedback.className = 'feedback correct';

                    if (correctlyPlaced === draggables.length) {
                        feedback.textContent = 'Congratulations! You have matched all the lessons correctly.';
                        saveScore();
                    }
                } else {
                    feedback.textContent = 'Wrong course! Try again.';
                    feedback.className = 'feedback incorrect';
                }
            }
        });
    });
});

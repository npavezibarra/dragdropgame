document.addEventListener('DOMContentLoaded', function() {
    const game = document.getElementById('draglearn-game');
    const draggables = document.querySelectorAll('.draggable');
    const dropZones = document.querySelectorAll('.drop-zone');
    const feedback = document.getElementById('feedback');
    const attemptId = game.dataset.attemptId;
    let correctlyPlaced = 0;
    let draggedItem = null;

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
                const correctCourse = draggedItem.dataset.course;
                const targetCourse = this.dataset.courseName;

                if (correctCourse === targetCourse) {
                    this.appendChild(draggedItem);
                    draggedItem.setAttribute('draggable', 'false');
                    correctlyPlaced++;
                    feedback.textContent = 'Correct!';
                    feedback.className = 'feedback correct';

                    if (correctlyPlaced === draggables.length) {
                        feedback.textContent = 'Congratulations! You have matched all the lessons correctly.';
                        // All items placed, send score to server
                        const score = correctlyPlaced; // Or any other scoring logic
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
                                if(response.success) {
                                    feedback.textContent += ' Your score has been saved.';
                                } else {
                                    feedback.textContent += ' There was an error saving your score.';
                                }
                            }
                        });
                    }
                } else {
                    feedback.textContent = 'Wrong course! Try again.';
                    feedback.className = 'feedback incorrect';
                }
            }
        });
    });
});

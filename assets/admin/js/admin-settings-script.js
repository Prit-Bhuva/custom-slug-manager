document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.sew-manager-tab');
    const sections = document.querySelectorAll('.sew-manager-section');

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Hide all sections
            sections.forEach(s => (s.style.display = 'none'));

            // Add active class to the clicked tab
            this.classList.add('active');
            // Show the corresponding section
            const sectionId = this.dataset.section;
            document.getElementById(`sew-manager-${sectionId}-section`).style.display = 'block';
        });
    });
});
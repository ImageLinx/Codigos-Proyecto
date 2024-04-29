document.getElementById('file-input').addEventListener('change', function() {
  document.getElementById('submit-btn').click();
});

window.addEventListener('DOMContentLoaded', (event) => {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  const uploaded = urlParams.get('uploaded');
  const file = urlParams.get('file');

  if (uploaded && file) {
    const title = document.getElementById('title');
    const description = document.getElementById('description');
    const uploadForm = document.getElementById('upload-form');
    const fileLink = decodeURIComponent(file);

    title.textContent = 'Imagen Subida';
    description.textContent = fileLink;
    uploadForm.style.display = 'none';
  }
});

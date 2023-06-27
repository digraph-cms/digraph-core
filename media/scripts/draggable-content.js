document.addEventListener('dragstart', (e) => {
  if ('dragContent' in e.target.dataset)
    e.dataTransfer.setData('text/plain',e.target.dataset.dragContent)
});

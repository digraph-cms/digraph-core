document.addEventListener('dragstart', (e) => {
  console.log(e);
  if ('dragContent' in e.target.dataset)
    e.dataTransfer.setData('text/plain',e.target.dataset.dragContent)
});

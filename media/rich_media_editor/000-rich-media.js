document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.getElementById('rich-media-editor__buttons');
  const interface = document.getElementById('rich-media-editor__interface');
  // move submit button out of interface
  Array.from(interface.getElementsByClassName('submit-button'))
    .forEach((e) => {
      e.classList.add('button--safe');
      buttons.appendChild(e);
    });
  // reload frame in opener
  const frame = (new URLSearchParams(location.search)).get('frame');
  if (frame && window.opener) window.opener.document.getElementById(frame).reloadFrame();
});

import { buildRefs } from '@/assets/scripts/helpers.js'

export default function (el) {
  const multiRefs = buildRefs(el, true)
  multiRefs.trigger.forEach(d => d.addEventListener('click', togglePanel))
}

function togglePanel (e) {
  const trigger = e.target
  const content = document.getElementById(trigger.getAttribute('aria-controls'))
  const isAriaExpanded = (trigger.getAttribute('aria-expanded') === 'true')
  trigger.setAttribute('aria-expanded', !isAriaExpanded)
  content.setAttribute('aria-hidden', isAriaExpanded)
}

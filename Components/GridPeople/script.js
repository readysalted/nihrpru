export default function (el) {
  const search = el.querySelector('[data-ref="search"]')
  const sort = el.querySelector('[data-ref="sort"]')
  const filterSelect = el.querySelector('[data-ref="filterSelect"]')
  const filterButtons = [...el.querySelectorAll('[data-ref="filter"]')]
  const items = el.querySelector('[data-ref="items"]')
  const people = [...el.querySelectorAll('[data-ref="person"]')]
  const resultCount = el.querySelector('[data-ref="resultCount"]')
  const resultLabel = el.querySelector('[data-ref="resultLabel"]')
  const empty = el.querySelector('[data-ref="empty"]')

  if (!search || !sort || !items || !resultCount || !resultLabel || !empty) return

  let activeGroup = 'all'

  const applyDirectoryState = () => {
    const query = search.value.trim().toLocaleLowerCase()
    const sortMode = sort.value
    const sortedPeople = [...people].sort((personA, personB) => {
      if (sortMode === 'name-asc') {
        return personA.dataset.personName.localeCompare(personB.dataset.personName)
      }
      if (sortMode === 'name-desc') {
        return personB.dataset.personName.localeCompare(personA.dataset.personName)
      }
      return Number(personA.dataset.personOrder) - Number(personB.dataset.personOrder)
    })

    let visibleCount = 0
    sortedPeople.forEach((person) => {
      const matchesSearch = !query || person.dataset.personSearch.includes(query)
      const roleGroups = person.dataset.personGroups.split(' ')
      const matchesGroup = activeGroup === 'all' || roleGroups.includes(activeGroup)
      const isVisible = matchesSearch && matchesGroup
      person.hidden = !isVisible
      visibleCount += isVisible ? 1 : 0
      items.append(person)
    })

    resultCount.textContent = visibleCount
    resultLabel.textContent = visibleCount === 1
      ? resultLabel.dataset.singular
      : resultLabel.dataset.plural
    empty.hidden = visibleCount !== 0
  }

  const setActiveGroup = (group) => {
    activeGroup = group
    filterButtons.forEach((button) => {
      const isActive = button.dataset.group === activeGroup
      button.classList.toggle('is-active', isActive)
      button.setAttribute('aria-pressed', isActive ? 'true' : 'false')
    })
    if (filterSelect) filterSelect.value = activeGroup
    applyDirectoryState()
  }

  const onSearch = () => applyDirectoryState()
  const onSort = () => applyDirectoryState()
  const onFilterSelect = () => setActiveGroup(filterSelect.value)
  const onFilterButton = (event) => setActiveGroup(event.currentTarget.dataset.group)

  search.addEventListener('input', onSearch)
  sort.addEventListener('change', onSort)
  filterSelect?.addEventListener('change', onFilterSelect)
  filterButtons.forEach((button) => button.addEventListener('click', onFilterButton))

  return () => {
    search.removeEventListener('input', onSearch)
    sort.removeEventListener('change', onSort)
    filterSelect?.removeEventListener('change', onFilterSelect)
    filterButtons.forEach((button) => button.removeEventListener('click', onFilterButton))
  }
}

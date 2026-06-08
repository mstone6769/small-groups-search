function smallGroupSearch() {
  const raw = (window.sgsData?.groups ?? [])
    .sort((a, b) => a.name.localeCompare(b.name))
    .map(g => ({
      ...g,
      email: g.email ? 'mailto:' + g.email : '',
      phone: g.phone ? 'tel:+1' + g.phone.replace(/\D/g, '') : '',
    }));

  const DAY_OPTIONS = ['Any day', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  const demoSet     = new Set(['All age groups', 'young adults | 18-35', 'adults', 'anyone | all ages']);
  const categorySet = new Set(['All categories', 'co-ed', 'family + parenting', 'men', 'women', 'spanish/español']);
  const typeSet     = new Set();

  raw.forEach(g => {
    g.filterDemographic.forEach(v => demoSet.add(v));
    g.filterCategory.forEach(v => categorySet.add(v));
    g.filterType.forEach(v => typeSet.add(v));
  });

  return {
    init() {
      Promise.resolve().then(function() {
        var el = document.getElementById('sgs-prerender');
        if (el) el.remove();
      });
    },

    groups:            raw,
    dayOptions:        DAY_OPTIONS,
    demographicOptions: [...demoSet],
    categoryOptions:   [...categorySet],
    typeOptions:       ['All types', ...[...typeSet].sort((a, b) => a.localeCompare(b))],

    search:            '',
    filterDays:        DAY_OPTIONS[0],
    filterDemographic: 'All age groups',
    filterCategory:    'All categories',
    filterType:        'All types',
    childcare:         false,
    online:            false,

    get isSearching() {
      return this.search !== ''
        || this.filterDays        !== this.dayOptions[0]
        || this.filterDemographic !== this.demographicOptions[0]
        || this.filterCategory    !== this.categoryOptions[0]
        || this.filterType        !== this.typeOptions[0]
        || this.childcare
        || this.online;
    },

    get filteredList() {
      const term = this.search.toLowerCase();
      return this.groups.filter(g => {
        if (this.childcare && g.childcareAvailable !== 'Yes')                                                              return false;
        if (this.online    && g.online             !== 'Yes')                                                              return false;
        if (this.filterDays        !== this.dayOptions[0]         && !g.filterDays.includes(this.filterDays))              return false;
        if (this.filterDemographic !== this.demographicOptions[0] && !g.filterDemographic.includes(this.filterDemographic)) return false;
        if (this.filterCategory    !== this.categoryOptions[0]    && !g.filterCategory.includes(this.filterCategory))      return false;
        if (this.filterType        !== this.typeOptions[0]        && !g.filterType.includes(this.filterType))              return false;
        if (!term) return true;
        return [g.name, g.leaders, g.target, g.description, g.meetsOn, g.location].join().toLowerCase().includes(term);
      });
    },

    groupClick(name, formLink) {
      if (typeof trackLink === 'function') trackLink(formLink, 'forms', null, 'Group: ' + name);
      window.location.href = formLink;
    },

    onSelectChange(label, value) {
      if (typeof gtag === 'function') gtag('event', 'click', { event_category: 'form', event_label: label + ': ' + value });
    },

    onCheckboxChange(label, value) {
      if (typeof gtag === 'function') gtag('event', 'click', { event_category: 'form', event_label: label + ': ' + value });
    },
  };
}

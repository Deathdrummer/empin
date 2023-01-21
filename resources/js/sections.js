window.loadSectionScripts = (ops = {}) => {
	if (_.isEmpty(ops)) throw Error('loadSectionScripts -> не переданы параметры!');
	
	const {
		guard,
		section,
	} = _.assign({
		guard: 'site',
		section: null,
	}, ops);
	
	
	//console.log(get);
	
	if (_.isNull(section)) throw Error('loadSectionScripts -> не указан параметр section');
	
	const data = require('./sections/'+guard+'/'+section+'/index.js');

	return data;
}
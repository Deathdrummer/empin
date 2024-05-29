export function getLastnameFromApplicant(applicant = null) {
	if (!applicant) return null;
	const res = applicant
		.replace(/ООО|ИП|ЗАО|МБУ|АО Торговый дом|АО|МКУ|ГБУ|СНТ|Глава|КФХ/g, '')
		.replace(/"/g, '')
		.trim()
		.replace(/\s+/g, ' ');
		
	const words = res.split(' ');
	return words[0] || null;
}
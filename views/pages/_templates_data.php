<?php
// Complaint templates: listing copy plus the prompts that prefill the report form.
$ssPrompt = function ($lines) {
    return implode("\n\n", $lines);
};
$SS_TEMPLATES = [
    ['id' => 1, 'cat' => 'Harassment', 'type' => 'harassment', 'title' => 'Workplace harassment complaint', 'desc' => 'Formal complaint for verbal abuse, inappropriate behaviour or a hostile environment at work.',
     'body' => $ssPrompt(['Person(s) involved and their role:', 'What was said or done:', 'Dates and times it happened:', 'Anyone who saw or heard it:', 'What I would like to happen next:'])],
    ['id' => 2, 'cat' => 'Stalking', 'type' => 'stalking', 'title' => 'Stalking incident report', 'desc' => 'Document a pattern of following or unwanted contact, with evidence and any protection you need.',
     'body' => $ssPrompt(['Who is following or contacting me (if known):', 'When and where it has happened (list each time):', 'Evidence I have (messages, photos, witnesses):', 'How it is affecting my safety:', 'Protection I am asking for:'])],
    ['id' => 3, 'cat' => 'Cybercrime', 'type' => 'cybercrime', 'title' => 'Online harassment report', 'desc' => 'Report cyberbullying, doxxing, image-based abuse or other digital harassment.',
     'body' => $ssPrompt(['Platform and account names or links:', 'What was posted or sent:', 'When it started and whether it is ongoing:', 'Screenshots or links I have saved:', 'What I want removed or stopped:'])],
    ['id' => 4, 'cat' => 'Harassment', 'type' => 'harassment', 'title' => 'Street harassment report', 'desc' => 'Catcalling, following or threatening behaviour in public, with room for witness details.',
     'body' => $ssPrompt(['Exact location:', 'What happened:', 'Description of the person(s):', 'Witnesses or nearby cameras:'])],
    ['id' => 5, 'cat' => 'Domestic', 'type' => 'domestic', 'title' => 'Domestic violence report', 'desc' => 'Confidential record of abuse at home, with space for what you need to be safe.',
     'body' => $ssPrompt(['Am I safe right now? (If not, use SOS or call 112.)', 'Person involved and relationship to me:', 'What has happened and how often:', 'Injuries or evidence:', 'Where I could go and who I trust:'])],
    ['id' => 6, 'cat' => 'Campus', 'type' => 'other', 'title' => 'Campus safety complaint', 'desc' => 'Poor lighting, broken locks or unsafe areas that need attention on campus.',
     'body' => $ssPrompt(['Exact place on campus:', 'What is unsafe about it:', 'When it is worst (time of day):', 'What would fix it:'])],
    ['id' => 7, 'cat' => 'Stalking', 'type' => 'stalking', 'title' => 'Digital stalking record', 'desc' => 'Track digital surveillance, unwanted contact, GPS tracking and social media monitoring.',
     'body' => $ssPrompt(['What I have noticed (tracking, logins, repeated contact):', 'Devices and accounts affected:', 'Dates and times:', 'Who I suspect and why:', 'Steps I have already taken:'])],
    ['id' => 8, 'cat' => 'Harassment', 'type' => 'harassment', 'title' => 'Sexual harassment (POSH)', 'desc' => 'Complaint under the POSH Act for an Internal Committee, with a timeline and evidence list.',
     'body' => $ssPrompt(['Respondent name and designation:', 'Timeline of incidents (date, place, what happened):', 'Witnesses:', 'Documents, messages or other evidence:', 'Relief I am seeking:'])],
    ['id' => 9, 'cat' => 'Cybercrime', 'type' => 'cybercrime', 'title' => 'Social media abuse report', 'desc' => 'Abusive messages, fake profiles, impersonation or targeted hate on social platforms.',
     'body' => $ssPrompt(['Platform and profile links:', 'What the account did:', 'Screenshots saved (dates):', 'Whether I have reported it to the platform:'])],
];

#!/usr/bin/env node
const path = require('path');

/* Configure START */
const pathBuildKram = path.resolve("../buildKramGhsvs");
const updateXml = `${pathBuildKram}/build/update.xml`;
const changelogXml = `${pathBuildKram}/build/changelog.xml`;
const releaseTxt = `${pathBuildKram}/build/release.txt`;
/* Configure END */

const replaceXml = require(`${pathBuildKram}/build/replaceXml.js`);
const helper = require(`${pathBuildKram}/build/helper.js`);

const pc = require(`${pathBuildKram}/node_modules/picocolors`);
const fse = require(`${pathBuildKram}/node_modules/fs-extra`);

let replaceXmlOptions = {
	"xmlFile": "",
	"zipFilename": "",
	"checksum": "",
	"dirname": __dirname,
	"jsonString": "",
	"versionSub": ""
};
let zipOptions = {};
let from = "";
let to = "";

const {
	filename,
	name,
	version,
} = require("./package.json");

const manifestFileName = `${filename}.xml`;
const Manifest = `${__dirname}/package/${manifestFileName}`;
const source = `./node_modules/hyphenopoly`;
const target = `./package/media/js/hyphenopoly`;
let versionSub = '';

(async function exec()
{
	let cleanOuts = [
		`./package`,
		`./dist`
	];
	await helper.cleanOut(cleanOuts);

	await helper.mkdir('./package');

	versionSub = await helper.findVersionSubSimple (
		path.join(__dirname, source, `package.json`),
		'Hyphenopoly');
	console.log(pc.magenta(pc.bold(`versionSub identified as: "${versionSub}"`)));

	replaceXmlOptions.versionSub = versionSub;

	from = `./media`;
	to = `./package/media`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	// Hyphenopoly_Loader.js loads ecplicitly Hyphenopoly.js which should be a minified one.
	from = `${source}/Hyphenopoly.js`;
	to = `${target}/Hyphenopoly.uncompressed.js`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `${source}/Hyphenopoly_Loader.js`;
	to = `${target}/Hyphenopoly_Loader.js`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `${source}/min/Hyphenopoly_Loader.js`;
	to = `${target}/Hyphenopoly_Loader.min.js`;;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	// Hyphenopoly_Loader.js loads ecplicitly Hyphenopoly.js which should be a minified one.
	from = `${source}/min/Hyphenopoly.js`;
	to = `${target}/Hyphenopoly.js`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `${source}/min/patterns`;
	to = `${target}/patterns`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `${source}/LICENSE`;
	to =  `${target}/LICENSE.txt`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	to =  `./package/LICENSE_Hyphenopoly.txt`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `./package.json`;
	to =  `./package/package.json`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `./src`;
	to = `./package`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	await helper.mkdir('./dist');

	from = path.resolve('package', 'media', 'joomla.asset.json');
	replaceXmlOptions.xmlFile = from;
	await replaceXml.main(replaceXmlOptions);

	await fse.copy(from, `./dist/joomla.asset.json`).then(
		answer => console.log(pc.yellow(pc.bold(
			`Copied "${from}" to "./dist".`)))
	);

	const zipFilename = `${name}-${version}_${versionSub}.zip`;

	replaceXmlOptions.xmlFile = Manifest;
	replaceXmlOptions.zipFilename = zipFilename;

	await replaceXml.main(replaceXmlOptions);
	await fse.copy(`${Manifest}`, `./dist/${manifestFileName}`).then(
		answer => console.log(pc.yellow(pc.bold(
			`Copied "${manifestFileName}" to "./dist".`)))
	);

	// ## Create zip file and detect checksum then.
	const zipFilePath = path.resolve(`./dist/${zipFilename}`);

	zipOptions = {
		"source": path.resolve("package"),
		"target": zipFilePath
	};
	await helper.zip(zipOptions)

	replaceXmlOptions.checksum = await helper._getChecksum(zipFilePath);

	// Bei diesen werden zuerst Vorlagen nach dist/ kopiert und dort erst "replaced".
	for (const file of [updateXml, changelogXml, releaseTxt])
	{
		from = file;
		to = `./dist/${path.win32.basename(file)}`;
		await helper.copy(from, to)

		replaceXmlOptions.xmlFile = path.resolve(to);
		await replaceXml.main(replaceXmlOptions);
	}

	cleanOuts = [
		`./package`
	];
	await helper.cleanOut(cleanOuts).then(
		answer => console.log(
			pc.cyan(pc.bold(pc.bgRed(`Finished. Good bye!`)))
		)
	);
})();

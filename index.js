const fs = require('fs');
const glob = require('glob');
const { hashElement } = require('folder-hash');

glob("**/app.json", async function (err, files) {

  if(err) {
    console.log("cannot read the folder, something goes wrong with glob", err)
  }

  let apptotal = files.length
  let apps = []
  let promises = [];

  for (const file of files) {
  //files.forEach(async function(file) {
    
    const options = {
      algho: 'sha1',
      encoding: 'hex'
    };

    let folder = file.replace('/app.json', '')

    let hash = await hashElement(folder, options)
    let filedata = fs.readFileSync(file)

    let parsed = JSON.parse(filedata)
    parsed.sha = hash.hash
    //console.log(parsed)
    apps.push(parsed)

  }

  let json = {
    appcount: apptotal,
    apps: apps
  }

  let data = JSON.stringify(json)

  var dir = './dist'
  
  if (!fs.existsSync(dir)){
    fs.mkdirSync(dir)
  }
  fs.writeFileSync(dir+'/list.json', data)
  fs.createReadStream('CNAME').pipe(fs.createWriteStream(dir+'/CNAME'))

})

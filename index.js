const fs = require('fs');
const glob = require('glob');
const { hashElement } = require('folder-hash');

glob("**/*.json", {"ignore":['list.json']}, function (err, files) {
  if(err) {
    console.log("cannot read the folder, something goes wrong with glob", err)
  }

  let apptotal = files.length
  let apps = []
  let promises = [];

  files.forEach(function(file) {
    
    const options = {
      algho: 'sha1',
      encoding: 'hex'
    };

    let folder = file.replace('/app.json', '')

    promises.push(
      hashElement(folder, options)
      .then(hash => {
        //console.log(hash.toString())
        fs.readFile(file, 'utf8', function(err, filedata) {
          if(err) {
            console.log("cannot read file", err)
          }
          let parsed = JSON.parse(filedata)
          parsed.sha = hash.hash
          apps.push(parsed)
  
        }) 
      })
      .catch(error => {
        return console.error('hashing failed:', error);
      })
    )
  })

  Promise.all(promises).then(() => {
    let json = {
      appcount: apptotal,
      apps: apps
    }

    let data = JSON.stringify(json);

    var dir = './dist';
    
    if (!fs.existsSync(dir)){
      fs.mkdirSync(dir);
    }
    fs.writeFileSync(dir+'/list.json', data);
  });


})

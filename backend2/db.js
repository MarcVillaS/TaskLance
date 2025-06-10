const mysql = require('mysql');

const connection = mysql.createConnection({
  host: '31.97.45.202',
  user: 'tasklance',
  password: '1G0gU8XlhYcJ5*sH',
  database: 'tasklance'
});

connection.connect((err) => {
  if (err) throw err;
  console.log('Conectado a la base de datos MySQL');
});

module.exports = connection;

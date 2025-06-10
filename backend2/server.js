const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');

const users = require('./routes/users');
const projects = require('./routes/projects');
const tasks = require('./routes/tasks');
const empresasRoutes = require('./routes/empresas');

const app = express();


app.use(cors());
app.use(bodyParser.json());

app.use('/api/empresas', empresasRoutes);
app.use('/api/users', users);
app.use('/api/projects', projects);
app.use('/api/tasks', tasks);

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Servidor corriendo en http://localhost:${PORT}`);
});

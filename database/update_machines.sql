-- Mettre à jour les machines avec les bonnes images Docker
UPDATE machines SET 
    docker_image = 'ctf/binaryninja:latest',
    description = 'Un environnement pour l''analyse de binaires et l''exploitation',
    cpu_limit = 1,
    memory_limit = 512,
    exposed_ports = '22'
WHERE id = 1;

UPDATE machines SET 
    docker_image = 'ctf/webmaster:latest',
    description = 'Un environnement pour les challenges web avec vulnérabilités',
    cpu_limit = 1,
    memory_limit = 512,
    exposed_ports = '80'
WHERE id = 2;

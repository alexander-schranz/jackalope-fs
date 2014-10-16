Jackalope Filesystem PHPCR implementation
=========================================

This is a WIP implementation to support a filesystem implementation of PHPCR.

The implementation is meant to be lightweight and ideal for testing PHPCR
components.

Connecting
----------

Connect as follows:

    $factory = new RepositoryFactoryFilesystem();
    $repository = $factory->getRepository(array(
        'path' => '/home/mystuff/somefolder',
    ));
    $credentials = new SimpleCredentials('admin', 'admin');
    $session = $repository->login($credentials);

Options:

- **path**: (required) Path to store data, indexes, etc.
- **search_enabled**: If search should be enabled or not (default true)

Limitations
-----------

### Querying

#### ZendSearch Lucene (native PHP)

Not supported:

- **Node type inheritance**: Currently node type inheritance is not taken into
  account - this should be fixed ASAP
- **Joins**: Will need to be implemented in a post processor
- **LOWERCASE, UPPERCASE, LENGTH operands**: Same as above
- **SQL and XPath query langauges**: Will probably never be implemented
- **Full text search**: Easy to implement if we add an additional search index

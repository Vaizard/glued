# The default Casbin Model

Casbin is an authorization library that supports access control models like ACL, RBAC, ABAC etc. for just about any language (PHP included). Models are defined by specific markup language (see Casbin documentation), that can be tested and prototyped online at https://casbin.org/en/editor.

### Glued's default model

Our model is a RBAC with domains/tenants and resource roles. This practically means, that we can assign permissions (RBAC policies) to

- subjects {subject} ({user} or {role}) and allow them to do {action} in {domain} on {object} (the {object} supports pattern matching)

  ```
  p, {user}, {domain}, /data/:subdata, {action}
  p, {role}, {domain}, /data/:subdata, {action}
  ```

- assign users to domain specific roles ({user} will have {role} in {domain})

   ```
   g, {user}, {role}, {domain}
   ```

- assign domain relationships

   ```
   g2, {domain_parent}, {domain_child}
   ```
  
   which grants permissions to {subject} which has {domain_parant} same permissions also on {domain_child}

### Example

Lets assume that we have domains

- USA
- Calif (California)
- LA (Los Angeles)

and roles for these domains

- admin (we describe the president of the USA as role `admin` to domain `USA`, the governor of Calif as role `admin` to domain `Calif`, etc.)
- general (in the sense of an army general) 

and users

- obama (US president)
- schwarzenegger (Calif governor)
- carol (LA mayor)
- patton (Army general)

we further want

- the president (Obama) to access all federal (USA) topics, but also all topics in every state (Calif) and every city (LA)
- all generals (i.e. Patton) to access all army topics (federal, state and city armies), but no other topics
- all governor (i.e. Schwarzenegger) all topics in his state (Calif) and every city in Calif (LA)
- all mayors (i.e. Carol) all topics in his city (LA)
- as an example, well also grant Schwarzenegger access to federal spacetech topics (by a policy bound to his username, not his role)

Our policies will look like this

```
g2, USA, Calif
g2, Calif, LA

g, obama, admin, USA
g, patton, general, USA
g, schwarzenegger, admin, Calif
g, carol, admin, LA

p, admin, USA, /usa/:topic, read
p, admin, Calif, /calif/:topic, read
p, admin, LA, /la/:topic, read
p, general, USA, /:location/army/*, read
p, schwarzenegger, USA, /usa/spacetech, read
```

With our model and the policies defined, the following example authorization requests will act as expected:

```
patton, USA, /usa/army/, read
patton, USA, /usa/army/battailion1, read
patton, USA, /calif/army/, read
obama, USA, /usa/army, read
obama, USA, /usa/health, read
obama, USA, /la/army, read
obama, USA, /calif/army, read
obama, Calif, /calif/army, read
schwarzenegger, Calif, /calif/army, read
schwarzenegger, Calif, /calif/health, read
schwarzenegger, Calif, /la/health, read
schwarzenegger, USA, /usa/health, read
schwarzenegger, USA, /usa/spacetech, read
carol, LA, /la/army, read
carol, LA, /la/lapd, read
```





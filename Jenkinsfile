pipeline {
    agent any
    environment {
        DOCKER_IMAGE       = 'labnoujsiham/devops-app'
        DOCKER_TAG         = "${BUILD_NUMBER}"           
        DOCKER_CREDENTIALS = 'dockerhub-credentials'
        GIT_REPO           = 'https://github.com/labnoujsiham/Dev_ops.git'
        GIT_CREDENTIALS    = 'github-credentials'       
    }
    stages {

        
        stage('Construction') {
            steps {
                echo "Construction de l'image Docker version ${DOCKER_TAG}..."
                sh 'docker build --no-cache -t ${DOCKER_IMAGE}:${DOCKER_TAG} .'
            }
        }

       
        stage('Tests') {
            steps {
                echo 'Exécution des tests en cours...'
                sh 'docker run --rm ${DOCKER_IMAGE}:${DOCKER_TAG} ./vendor/bin/phpunit tests/'
            }
        }

      
        stage('Publication') {
            steps {
                echo "Publication de l'image ${DOCKER_IMAGE}:${DOCKER_TAG} sur Docker Hub..."
                withCredentials([usernamePassword(
                    credentialsId: "${DOCKER_CREDENTIALS}",
                    usernameVariable: 'DOCKER_USER',
                    passwordVariable: 'DOCKER_PASS'
                )]) {
                    sh 'echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin'
                    sh 'docker push ${DOCKER_IMAGE}:${DOCKER_TAG}'
                }
            }
        }

       
        stage('Déploiement') {
            steps {
                
                echo "Mise à jour du tag dans deployment.yaml → ${DOCKER_IMAGE}:${DOCKER_TAG}"
                withCredentials([usernamePassword(
                    credentialsId: "${GIT_CREDENTIALS}",
                    usernameVariable: 'GIT_USER',
                    passwordVariable: 'GIT_PASS'
                )]) {
                    sh '''
                        # Configurer git
                        git config user.email "jenkins@pipeline.com"
                        git config user.name "Jenkins"

                        # Remplacer le tag de l'image dans deployment.yaml
                        sed -i "s|image: ${DOCKER_IMAGE}:.*|image: ${DOCKER_IMAGE}:${DOCKER_TAG}|" deploiement/deployment.yaml

                        # Vérifier le changement
                        echo "Nouveau contenu deployment.yaml :"
                        grep "image:" deploiement/deployment.yaml

                        # Commiter et pousser le changement
                        git add deploiement/deployment.yaml
                        git commit -m "Jenkins : mise à jour image vers ${DOCKER_IMAGE}:${DOCKER_TAG}"
                        git push https://${GIT_USER}:${GIT_PASS}@github.com/labnoujsiham/Dev_ops.git HEAD:main
                    '''
                }

                
                echo 'Argo CD détecte le changement et synchronise le cluster...'
                withCredentials([file(credentialsId: 'kubeconfig', variable: 'KUBECONFIG')]) {
                    sh '''
                        # Attendre que le déploiement soit terminé
                        kubectl rollout status deployment/devops-app --timeout=120s
                        echo "✅ Déploiement terminé avec succès !"
                    '''
                }
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline terminé avec succès! Image déployée : ${DOCKER_IMAGE}:${DOCKER_TAG}"
        }
        failure {
            echo '❌ Le pipeline a échoué — vérifiez les logs ci-dessus.'
        }
    }
}